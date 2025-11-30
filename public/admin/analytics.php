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

if (!$auth->isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$event = new Event();
$analytics = new AnalyticsService();

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

// Handle export request
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $filename = 'events_report_' . date('Ymd_His') . '.csv';

    // Send CSV headers (Excel friendly)
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Output BOM for Excel to handle UTF-8
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    // Header row
    fputcsv($out, ['Nama Event', 'Tanggal Event', 'Jumlah Peserta', 'Nama Peserta', 'Email Peserta']);

    // Get all events with participants
    $all_events = $event->getAllEvents();
    foreach ($all_events as $evt) {
        $participants = $event->getEventParticipants($evt['id']);
        
        if (!empty($participants)) {
            foreach ($participants as $p) {
                fputcsv($out, [
                    $evt['title'],
                    $evt['event_date'],
                    count($participants),
                    $p['name'],
                    $p['email']
                ]);
            }
        } else {
            fputcsv($out, [
                $evt['title'],
                $evt['event_date'],
                0,
                '-',
                '-'
            ]);
        }
    }
    fclose($out);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Analytics - Admin</title>
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
                        <a class="nav-link" href="participants.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Participants
                        </a>
                        <div class="sb-sidenav-menu-heading">Analytics</div>
                        <a class="nav-link active" href="analytics.php">
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
                    <h1 class="mt-4">Analytics & Reports</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Overview and export</li>
                    </ol>

                    <div class="mb-3">
                        <a href="analytics.php?export=1" class="btn btn-success"><i class="fas fa-file-excel"></i> Download Report (Excel)</a>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-chart-bar me-1"></i>
                                        Events & Participants Comparison
                                    </div>
                                    <form method="GET" class="d-inline">
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
                                    <canvas id="eventParticipantsChart" width="100%" height="60"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="footer py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Event Management 2024</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
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
