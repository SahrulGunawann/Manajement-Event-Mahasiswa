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
        /* Notification popup animation */
        .notif-popup {
            transition: opacity 180ms ease, transform 180ms ease;
            opacity: 0;
            transform: translateY(-6px) scale(0.98);
            will-change: opacity, transform;
        }
        .notif-popup.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        /* Ensure popup sits above other content */
        #notifPopup {
            z-index: 2000;
        }
        /* Keep notification icon fixed at right of navbar so it doesn't drop on mobile collapse */
        .navbar .container { position: relative; }
        .notif-wrapper { margin-left: 20px; }
    </style>
</head>
<body>
    <div class="content">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
            <div class="container d-flex align-items-center">
                <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand me-auto" href="index.php"  aria-label="Dashboard">🎯 Event Mahasiswa</a>

                

                <div class="collapse navbar-collapse flex-grow-1" id="mainNavbar">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <?php if ($auth->isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="events.php">Events</a>
                            </li>

                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="profile.php">Profile</a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link" href="logout.php">Logout</a>
                            </li>
                            <!-- mobile Notifications link removed (icon remains visible) -->

                            <li class="nav-item dropdown d-none d-lg-block ms-2">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if ($auth->isLoggedIn()): ?>
                    <?php $unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']); ?>
                    <div class="notif-wrapper d-flex align-items-center">
                        <button id="notifButton" class="btn btn-link text-light position-relative p-0" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" role="button" type="button">
                            <span class="fs-5">🔔</span>
                            <?php if ($unreadCount > 0): ?>
                                <span id="notifCountBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notifPopup" class="dropdown-menu dropdown-menu-end shadow p-0 notif-popup" role="menu" aria-labelledby="notifButton" tabindex="-1" style="display:none; min-width:320px;">
                            <div id="notifPopupContent" class="p-2" tabindex="0">Loading...</div>
                        </div>
                    </div>
                <?php endif; ?>
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
                                                                    ⏰ 
                                                                    <?php
                                                                        // Prefer original Google datetime if available (preserves timezone)
                                                                        if (!empty($event_item['start_raw'])) {
                                                                            try {
                                                                                $dt = new DateTime($event_item['start_raw']);
                                                                                // Format to local server time; you can change timezone as needed
                                                                                $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
                                                                                echo $dt->format('H:i');
                                                                            } catch (Exception $e) {
                                                                                echo htmlspecialchars($event_item['event_time']);
                                                                            }
                                                                        } else {
                                                                            echo htmlspecialchars($event_item['event_time']);
                                                                        }
                                                                    ?>
                                                                    <br>
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
        // Notification popup behavior: fetch content and toggle popup near icon
        (function(){
            var notifButton = document.getElementById('notifButton');
            var notifPopup = document.getElementById('notifPopup');
            var notifContent = document.getElementById('notifPopupContent');

            // If popup exists inside navbar, move it to body so it's not clipped by overflow
            if (notifPopup && notifPopup.parentNode !== document.body) {
                document.body.appendChild(notifPopup);
            }

            function closePopup() {
                if (!notifPopup) return;
                notifPopup.classList.remove('show');
                if (notifButton) notifButton.setAttribute('aria-expanded', 'false');
                // wait for CSS transition then hide
                setTimeout(function(){ if (notifPopup) notifPopup.style.display = 'none'; }, 220);
            }

            function openPopup() {
                if (!notifPopup || !notifButton) return;
                var rect = notifButton.getBoundingClientRect();
                notifPopup.style.position = 'absolute';
                // make visible but hidden to measure
                notifPopup.style.display = 'block';
                notifPopup.style.visibility = 'hidden';
                // compute width
                var width = notifPopup.offsetWidth || 320;
                var left = rect.right + window.scrollX - width;
                // ensure not off-screen
                left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
                notifPopup.style.left = left + 'px';
                notifPopup.style.top = (rect.bottom + window.scrollY + 6) + 'px';
                // animate
                requestAnimationFrame(function(){
                    notifPopup.style.visibility = 'visible';
                    notifPopup.classList.add('show');
                    if (notifButton) notifButton.setAttribute('aria-expanded', 'true');
                    // move focus into first focusable element in popup (if any)
                    setTimeout(function(){
                        var firstLink = notifPopup.querySelector('a, button, [tabindex]:not([tabindex="-1"])');
                        if (firstLink) firstLink.focus(); else notifPopup.focus();
                    }, 60);
                });
            }

            function fetchAndShow() {
                if (!notifContent) return;
                notifContent.innerHTML = 'Loading...';
                fetch('get_notifications.php', { credentials: 'same-origin' })
                .then(function(r){ return r.text(); })
                .then(function(html){
                    notifContent.innerHTML = html;
                    openPopup();
                }).catch(function(){
                    notifContent.innerHTML = '<div class="p-3 text-muted">Failed to load</div>';
                    openPopup();
                });

                // mark all as read and remove badges
                fetch('mark_notifications_read.php', { method: 'POST', credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (data && data.success) {
                        var b = document.getElementById('notifCountBadge'); if (b) b.remove();
                        var bm = document.getElementById('notifCountBadgeMobile'); if (bm) bm.remove();
                    }
                }).catch(function(){});
            }

            if (notifButton) {
                notifButton.addEventListener('click', function(e){
                    e.preventDefault();
                    // toggle
                    if (notifPopup.style.display === 'block') {
                        closePopup();
                    } else {
                        fetchAndShow();
                    }
                });
            }

            // mobile collapsed menu no longer contains a Notifications text link;
            // users should use the bell icon (always visible) to open notifications.

            // keyboard accessibility
            if (notifButton) {
                notifButton.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (notifPopup.style.display === 'block') closePopup(); else fetchAndShow();
                    } else if (e.key === 'Escape') {
                        closePopup();
                    }
                });
            }

            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') closePopup();
            });

            // close popup on outside click
            document.addEventListener('click', function(e){
                if (!notifPopup || !notifButton) return;
                if (notifPopup.style.display !== 'block') return;
                if (notifPopup.contains(e.target) || notifButton.contains(e.target)) return;
                closePopup();
            });
        })();
    </script>
</body>
</html>