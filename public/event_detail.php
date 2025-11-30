<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$eventModel = new Event();
$message = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$id = (int) $_GET['id'];
$event = $eventModel->getEventById($id);

if (!$event) {
    header('Location: events.php');
    exit;
}

$participants = $eventModel->getEventParticipants($id);
$participantCount = count($participants);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $user = $auth->getUser();
    if (!$user) {
        $message = ['type' => 'danger', 'text' => 'Anda harus login untuk mendaftar.'];
    } else {
        // Check capacity
        if (!empty($event['max_participants']) && $participantCount >= (int)$event['max_participants']) {
            $message = ['type' => 'warning', 'text' => 'Maaf, kuota peserta sudah penuh.'];
        } else {
            $registered = $eventModel->registerForEvent($id, $user['id']);
            if ($registered) {
                // refresh participants
                $participants = $eventModel->getEventParticipants($id);
                $participantCount = count($participants);
                $message = ['type' => 'success', 'text' => 'Pendaftaran berhasil. Terima kasih!'];
            } else {
                $message = ['type' => 'danger', 'text' => 'Gagal mendaftar. Anda mungkin sudah terdaftar.'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Event - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge-muted { background: #f1f1f1; color: #333; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">🎯 Event Mahasiswa</a>
            <div class="navbar-nav ms-auto">
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
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2><?= htmlspecialchars($event['title']) ?></h2>
                <p class="text-muted">
                    📅 <?= date('d M Y', strtotime($event['event_date'])) ?> &nbsp;|&nbsp; ⏰ <?= htmlspecialchars($event['event_time']) ?>
                </p>

                <p><strong>Lokasi:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                <p>
                    &nbsp;
                    <span class="badge bg-info p-2">Peserta: <?= $participantCount ?><?= !empty($event['max_participants']) ? ' / ' . (int)$event['max_participants'] : '' ?></span>
                </p>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
                <?php endif; ?>

                <form method="post">
                    <button type="submit" name="register" class="btn btn-primary">Daftar Sekarang</button>
                    <a href="events.php" class="btn btn-secondary">Kembali ke Daftar Event</a>
                </form>
            </div>

            <?php if ($auth->isAdmin()): ?>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Daftar Peserta</h5>
                        <?php if (!empty($participants)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($participants as $p): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($p['name']) ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($p['email']) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Belum ada peserta terdaftar.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
