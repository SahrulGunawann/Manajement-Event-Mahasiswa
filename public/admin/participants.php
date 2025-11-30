<?php
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/Database.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Hanya admin yang boleh mengakses
if (!$auth->isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$message = null;

// Handle delete participant (registration removal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && is_numeric($_POST['delete'])) {
    $delId = (int) $_POST['delete'];
    $stmt = $conn->prepare('DELETE FROM event_participants WHERE id = ?');
    $stmt->bind_param('i', $delId);
    if ($stmt->execute()) {
        $message = ['type' => 'success', 'text' => 'Pendaftaran peserta dihapus.'];
    } else {
        $message = ['type' => 'danger', 'text' => 'Gagal menghapus pendaftaran.'];
    }
    $stmt->close();
}

$sql = "SELECT ep.id as ep_id, ep.registered_at, ep.status, u.name as user_name, u.email, e.title as event_title
        FROM event_participants ep
        JOIN users u ON ep.user_id = u.id
        JOIN events e ON ep.event_id = e.id
        ORDER BY ep.registered_at DESC";
$result = $conn->query($sql);
$participants = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $participants[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Participants - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">Event Management Admin</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                    <li><hr class="dropdown-divider" /></li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Core</div>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <div class="sb-sidenav-menu-heading">Management</div>
                        <a class="nav-link" href="events.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Events Management
                        </a>
                        <a class="nav-link active" href="participants.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Participants
                        </a>
                        <div class="sb-sidenav-menu-heading">Analytics</div>
                        <a class="nav-link" href="analytics.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                            Analytics & Reports
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
            </nav>
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Participants</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">List of registered participants</li>
                    </ol>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Registered Participants
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Event</th>
                                        <th>Registered At</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($participants)): ?>
                                        <?php foreach ($participants as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['user_name']) ?></td>
                                                <td><?= htmlspecialchars($p['email']) ?></td>
                                                <td><?= htmlspecialchars($p['event_title']) ?></td>
                                                <td><?= htmlspecialchars($p['registered_at']) ?></td>
                                                <td><?= htmlspecialchars($p['status']) ?></td>
                                                <td>
                                                    <form method="post" style="display:inline" onsubmit="return confirm('Hapus pendaftaran peserta ini?');">
                                                        <input type="hidden" name="delete" value="<?= (int)$p['ep_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">Tidak ada peserta terdaftar.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Event Management 2024</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
