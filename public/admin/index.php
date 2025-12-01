<?php
session_start();
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/Event.php';
require_once __DIR__ . '/../../classes/AnalyticsService.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Jika user bukan admin, redirect ke user dashboard
if (!$auth->isAdmin()) {
    header('Location: ../index.php');
    exit;
}
$event = new Event();
$analytics = new AnalyticsService();

// Data untuk dashboard
$total_events = $event->getTotalEvents();
$total_participants = $event->getTotalParticipants();
$upcoming_events = $event->getAllEvents();
$recent_registrations = $event->getRecentRegistrations(5);

// Data untuk charts
$events_per_month = $analytics->getEventsPerMonth();
$participants_per_event = $analytics->getParticipantsPerEvent();

// Handle month selection for events vs participants chart
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get events and participants for selected month
$events_by_month = $event->getEventsByMonth($selected_month, $selected_year);
$event_vs_participants = [];
foreach ($events_by_month as $evt) {
    $participants = $event->getEventParticipants($evt['id']);
    $event_vs_participants[] = [
        'title' => $evt['title'],
        'participant_count' => count($participants)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Dashboard - Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table-scroll {
            max-height: 500px;
            overflow-y: auto;
            display: block;
        }
        .table-scroll table {
            display: table;
            width: 100%;
        }
        /* Position navbar user section to the right */
        .sb-topnav {
            display: flex;
            align-items: center;
        }
        .sb-topnav .navbar-brand {
            flex-shrink: 0;
            margin-right: auto;
        }
        .sb-topnav .navbar-nav {
            margin-left: auto;
            margin-right: 0;
        }
        .sb-topnav .dropdown-menu-end {
            right: 0;
            left: auto;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <!-- Navigation -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <button class="btn btn-link btn-sm" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <a class="navbar-brand ps-3" href="index.php">Event Management Admin</a>
        
        <!-- Navbar-->
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

    <!-- Sidebar -->
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

        <!-- Main Content -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Overview</li>
                    </ol>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Events</div>
                                            <div class="h5 mb-0"><?= $total_events ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Participants</div>
                                            <div class="h5 mb-0"><?= $total_participants ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Upcoming Events</div>
                                            <div class="h5 mb-0"><?= count($upcoming_events) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">This Month Events</div>
                                            <div class="h5 mb-0"><?= count($events_per_month) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-pie fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['reminders_sent'])): ?>
                        <div class="alert alert-info mt-3">Reminders created: <?= (int)$_GET['reminders_sent'] ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <form method="GET" action="run_reminders.php" style="display:inline;">
                            <button type="submit" class="btn btn-sm btn-primary">Run Reminders Now</button>
                        </form>
                        <small class="text-muted ms-2">(Triggers reminder notifications for events happening tomorrow)</small>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-x0.5-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Events & Participants Comparison
                                    <form method="GET" style="display: inline; float: right;" class="d-inline">
                                        <select name="month" onchange="this.form.submit()" class="form-select form-select-sm d-inline w-auto">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </form>
                                </div>
                                <div class="card-body">
                                    <canvas id="eventParticipantsChart" width="100%" height="40"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Events Table -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            All Events
                        </div>
                        <div class="card-body table-scroll">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Participants</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($upcoming_events as $event_item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($event_item['title']) ?></td>
                                        <td><?= date('M d, Y', strtotime($event_item['event_date'])) ?></td>
                                        <td><?= htmlspecialchars($event_item['location']) ?></td>
                                        <td>
                                            <?php 
                                            $participants = $event->getEventParticipants($event_item['id']);
                                            echo count($participants) . ' / ' . $event_item['max_participants'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (strtotime($event_item['event_date']) > time()): ?>
                                                <span class="badge bg-success">Upcoming</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
    
    <script>
        // Events vs Participants Chart (Month selected)
        const eventParticipantsCtx = document.getElementById('eventParticipantsChart').getContext('2d');
        const eventParticipantsChart = new Chart(eventParticipantsCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach($event_vs_participants as $e): ?>'<?= htmlspecialchars($e['title']) ?>',<?php endforeach; ?>],
                datasets: [{
                    label: 'Participants',
                    data: [<?php foreach($event_vs_participants as $e): ?><?= (int)$e['participant_count'] ?>,<?php endforeach; ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    </script>
</body>
</html>