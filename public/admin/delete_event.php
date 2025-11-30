<?php
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Event.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$eventModel = new Event();

// Get event ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$event_id = (int) $_GET['id'];
$event = $eventModel->getEventById($event_id);

if (!$event) {
    header('Location: events.php');
    exit;
}

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($eventModel->deleteEvent($event_id)) {
        // Redirect with success message
        header('Location: events.php?success=1&action=delete');
        exit;
    } else {
        $error = 'Failed to delete event!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Delete Event - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                        <a class="nav-link active" href="events.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                            Events Management
                        </a>
                        <a class="nav-link" href="participants.php">
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
                    <h1 class="mt-4">Delete Event</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="events.php">Events Management</a></li>
                        <li class="breadcrumb-item active">Delete Event</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4 border-danger">
                                <div class="card-header bg-danger text-white">
                                    <i class="fas fa-trash me-1"></i>
                                    Confirm Delete Event
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-danger">
                                        <strong>Are you sure?</strong> This action cannot be undone.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Event Title:</strong>
                                        <p><?= htmlspecialchars($event['title']) ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Event Date:</strong>
                                        <p><?= htmlspecialchars($event['event_date']) ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Location:</strong>
                                        <p><?= htmlspecialchars($event['location']) ?></p>
                                    </div>

                                    <form method="POST">
                                        <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete Event
                                        </button>
                                        <a href="events.php" class="btn btn-secondary">Cancel</a>
                                    </form>
                                </div>
                            </div>
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
