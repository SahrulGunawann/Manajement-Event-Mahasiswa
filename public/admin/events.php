<?php
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Event.php';
require_once __DIR__ . '/../../classes/NotificationService.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$event = new Event();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = $_POST['location'] ?? '';
    $max_participants = $_POST['max_participants'] ?? 50;
    
    if (empty($title) || empty($event_date) || empty($location)) {
        $error = 'Title, date, and location are required!';
    } else {
        $createdId = $event->createEvent($title, $description, $event_date, $event_time, $location, $max_participants, $_SESSION['user_id']);
        if ($createdId) {
            // Send notification to all users about the new event
            $notificationService = new NotificationService();
            $notificationService->notifyAllUsersOnNewEvent($createdId);
            // PRG pattern: redirect to self to avoid duplicate submission on refresh
            header('Location: events.php?success=1&action=create');
            exit;
        } else {
            $error = 'Failed to create event!';
        }
    }
}

// Display success message if redirected from POST/action
$show_success = isset($_GET['success']) && $_GET['success'] == '1';
if ($show_success) {
    $action = $_GET['action'] ?? 'create';
    if ($action === 'delete') {
        $message = 'Event deleted successfully!';
    } elseif ($action === 'update') {
        $message = 'Event updated successfully!';
    } else {
        $message = 'Event created successfully!';
    }
}

// Get all events for listing
$all_events = $event->getAllEvents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Events Management - Admin</title>
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
                    <h1 class="mt-4">Events Management</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Create and manage events</li>
                    </ol>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xl-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-plus me-1"></i>
                                    Create New Event
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Event Title</label>
                                            <input type="text" class="form-control" name="title" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3"></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Event Date</label>
                                                <input type="date" class="form-control" name="event_date" required min="<?= date('Y-m-d') ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Event Time</label>
                                                <input type="time" class="form-control" name="event_time" value="14:00">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <input type="text" class="form-control" name="location" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Max Participants</label>
                                            <input type="number" class="form-control" name="max_participants" value="50" min="1">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Create Event</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-table me-1"></i>
                                    All Events (<?= count($all_events) ?>)
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($all_events)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Date</th>
                                                        <th>Location</th>
                                                        <th>Participants</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($all_events as $evt): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($evt['title']) ?></td>
                                                        <td><?= date('d M Y', strtotime($evt['event_date'])) ?></td>
                                                        <td><?= htmlspecialchars($evt['location']) ?></td>
                                                        <td>
                                                            <?php 
                                                            $participants = $event->getEventParticipants($evt['id']);
                                                            echo count($participants) . ' / ' . $evt['max_participants'];
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <a href="edit_event.php?id=<?= $evt['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                                            <a href="delete_event.php?id=<?= $evt['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event?')">Delete</a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No events found.</p>
                                    <?php endif; ?>
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