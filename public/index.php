<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Event.php';
require_once __DIR__ . '/../classes/NotificationService.php';

$auth = new Auth();
$event = new Event();
$notificationService = new NotificationService();

// Jika admin sudah login, redirect ke admin dashboard
if ($auth->isLoggedIn() && $auth->isAdmin()) {
    header('Location: admin/index.php');
    exit;
}
// Cleanup event yang sudah lewat
$event->cleanupPastEvents();

// Check untuk upcoming events notifications
$notificationService->checkUpcomingEvents();

// GET EVENTS BULAN INI - VERSI SIMPLE
$current_month = date('m');
$current_year = date('Y');

// Debug: cek nilai month dan year
// echo "Debug: Month = $current_month, Year = $current_year";

$events_this_month = $event->getEventsByMonth($current_month, $current_year, 6);

// Get all events untuk calendar
$all_events = $event->getAllEvents();

// Get user notifications jika sudah login
$userNotifications = [];
if ($auth->isLoggedIn()) {
    $userNotifications = $notificationService->getUserNotifications($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .event-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand {
            font-weight: bold;
        }
        
        /* FIX FOOTER & NAVBAR STICKY */
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1;
        }
        footer {
            margin-top: auto;
        }
        
        /* Sticky Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        /* Calendar Styling */
        #calendar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .month-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">🎯 Event Mahasiswa</a>
                
                <div class="navbar-nav ms-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <!-- Tampilan setelah login -->
                        <a class="nav-link" href="index.php">Dashboard</a>
                        <a class="nav-link" href="events.php">Events</a>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                👋 <?= htmlspecialchars($_SESSION['user_name']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                        <!-- Notification Bell -->
                        <?php if ($auth->isLoggedIn()): ?>
                        <div class="nav-item dropdown">
                            <?php $unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']); ?>
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                🔔
                                <?php if ($unreadCount > 0): ?>
                                    <span id="notifCountBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" id="notificationMenu">
                                <?php if (!empty($userNotifications)): ?>
                                    <?php foreach($userNotifications as $notif): ?>
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <strong><?= htmlspecialchars($notif['title']) ?></strong><br>
                                            <small><?= htmlspecialchars($notif['message']) ?></small>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="dropdown-item text-muted">No notifications</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Tampilan sebelum login -->
                        <a class="nav-link" href="login.php">Login</a>
                        <a class="nav-link" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container text-center">
                <h1 class="display-4 fw-bold">Selamat Datang di Event Management</h1>
                <p class="lead">Temukan dan ikuti event-event seru kampus bersama mahasiswa lainnya</p>
                <?php if (!$auth->isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-light btn-lg mt-3">Daftar Sekarang</a>
                <?php else: ?>
                    <a href="#events-section" class="btn btn-light btn-lg mt-3">Lihat Event Mendatang</a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Events Section -->
        <section id="events-section" class="py-5">
            <div class="container">
                <div class="month-header text-center">
                    <h3 class="mb-0">Event Bulan <?= date('F Y') ?></h3>
                    <small class="text-light">Menampilkan event kampus bulan ini</small>
                </div>
                
                <?php if (empty($events_this_month)): ?>
                    <!-- HANYA TAMPIL JIKA TIDAK ADA EVENT -->
                    <div class="text-center py-5">
                        <div class="alert alert-info mx-auto" style="max-width: 500px;">
                            <h4>📅 Tidak ada event</h4>
                            <p>Belum ada event yang dijadwalkan untuk bulan <?= date('F Y') ?>.</p>
                            
                            <?php if ($auth->isLoggedIn() && $auth->isAdmin()): ?>
                                <a href="admin/events.php" class="btn btn-success">➕ Buat Event Baru</a>
                            <?php elseif ($auth->isLoggedIn()): ?>
                                <p class="text-muted mb-0">Hubungi admin untuk menambahkan event.</p>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- TAMPIL JIKA ADA EVENT -->
                    <div class="row">
                        <?php foreach($events_this_month as $event_item): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card event-card shadow">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($event_item['title']) ?></h5>
                                    <p class="card-text text-muted">
                                        📅 <?= date('d M Y', strtotime($event_item['event_date'])) ?><br>
                                        ⏰ <?= $event_item['event_time'] ?><br>
                                        📍 <?= htmlspecialchars($event_item['location']) ?>
                                    </p>
                                    <p class="card-text"><?= htmlspecialchars(substr($event_item['description'], 0, 100)) ?>...</p>
                                    
                                    <?php if ($auth->isLoggedIn()): ?>
                                        <a href="event_detail.php?id=<?= $event_item['id'] ?>" class="btn btn-primary">Detail Event</a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary">Login untuk Daftar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- More Events Button -->
                    <div class="text-center mt-4">
                        <a href="events.php" class="btn btn-outline-primary btn-lg">Lihat Semua Events</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Calendar Section -->
        <?php if ($auth->isLoggedIn()): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5">Calendar Events</h2>
                <div id="calendar"></div>
            </div>
        </section>
        <?php endif; ?>
    </div> <!-- end .content -->

    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container text-center">
            <p>&copy; 2024 Event Management Mahasiswa. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    
    <!-- Data Events untuk JavaScript -->
    <script>
        var calendarEvents = <?= json_encode($all_events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            if (calendarEl) {
                // Convert data untuk FullCalendar
                var eventsForCalendar = calendarEvents.map(function(event) {
                    return {
                        title: event.title,
                        start: event.event_date,
                        description: event.description,
                        location: event.location
                    };
                });

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: eventsForCalendar,
                    eventClick: function(info) {
                        alert(
                            'Event: ' + info.event.title + '\n' +
                            'Date: ' + info.event.start.toLocaleDateString() + '\n' +
                            'Location: ' + info.event.extendedProps.location + '\n' +
                            'Description: ' + info.event.extendedProps.description
                        );
                    }
                });
                calendar.render();
            }
        });
    </script>
    <script>
        // Hide notification badge when user opens the notification dropdown (mark as read)
        (function() {
            var notifDropdown = document.getElementById('notificationDropdown');
            if (!notifDropdown) return;

            notifDropdown.addEventListener('show.bs.dropdown', function () {
                // Send request to mark notifications as read
                fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        var badge = document.getElementById('notifCountBadge');
                        if (badge) badge.remove();
                    }
                }).catch(function(err) {
                    console.error('Failed to mark notifications read', err);
                });
            });
        })();
    </script>
</body>
</html>