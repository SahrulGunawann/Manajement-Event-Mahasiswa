<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Event.php';
require_once __DIR__ . '/../classes/NotificationService.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$event = new Event();
$all_events = $event->getAllEvents();
$notificationService = new NotificationService();
// helper for active nav link
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events - Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        /* Notification popup animation (kept minimal for navbar consistency) */
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
        #notifPopup { z-index: 2000; }
        .navbar .container { position: relative; }
        .notif-wrapper { position: absolute; right: 12px; top: 8px; z-index: 2100; }
        .navbar-brand { font-weight: bold; }
        .navbar-nav .nav-link { padding: 0.45rem 0.75rem; color: rgba(255,255,255,0.9); }
        .navbar-nav .nav-link.active { background: rgba(255,255,255,0.06); border-radius: 6px; color: #fff; }
        .event-card {
            transition: transform 0.3s;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container d-flex align-items-center">
            <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand me-auto" href="index.php">🎯 Event Mahasiswa</a>

            <div class="collapse navbar-collapse flex-grow-1" id="mainNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'events.php') ? 'active' : '' ?>" href="events.php">Events</a>
                    </li>

                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>

                    <li class="nav-item dropdown d-none d-lg-block ms-2">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            👋 <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
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

    <div class="container mt-4">
        <h1 class="text-center mb-5">All Events</h1>
        
        <div class="row">
            <?php if (!empty($all_events)): ?>
                <?php foreach($all_events as $event): ?>
                <div class="col-md-6 mb-4">
                    <div class="card event-card shadow">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                            <p class="card-text text-muted">
                                📅 <?= date('d M Y', strtotime($event['event_date'])) ?><br>
                                ⏰ <?= $event['event_time'] ?><br>
                                📍 <?= htmlspecialchars($event['location']) ?>
                            </p>
                            <p class="card-text"><?= htmlspecialchars($event['description']) ?></p>
                            <a href="event_detail.php?id=<?= $event['id'] ?>" class="btn btn-primary">Detail & Daftar</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Tidak ada event.</p>
                    <a href="index.php" class="btn btn-primary">Kembali ke Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>