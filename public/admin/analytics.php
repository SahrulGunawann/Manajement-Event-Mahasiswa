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

// Get analytics summary dan recommendations
$analytics_summary = $analytics->getAnalyticsSummary();
$recommendations = $analytics->getRecommendations();

// Get time series data (sesuai ketentuan)
$event_trend = $analytics->getEventTrendData(6);
$monthly_trend = $analytics->getMonthlyParticipationTrend();

// Get category data for pie/bar chart (sesuai ketentuan)
$category_data = $analytics->getParticipantsByCategory();

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

// Get top popular events (by participant count)
$top_events = $analytics->getParticipantsPerEvent();

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
    <style>
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
        
        /* Border left colors for cards */
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
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

                    <!-- Analytics Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Event Bulan Ini</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $analytics_summary['events_this_month'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Peserta Aktif</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $analytics_summary['active_participants'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tipe Event Populer</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($analytics_summary['popular_event_type']) ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-fire fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Rata-rata Peserta</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $analytics_summary['avg_participants'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations Section -->
                    <?php if (!empty($recommendations)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-lightbulb me-2"></i>Rekomendasi Sistem
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($recommendations as $rec): ?>
                                    <div class="alert alert-<?= $rec['type'] === 'low_participation' ? 'warning' : 'info' ?> mb-2">
                                        <strong><?= htmlspecialchars($rec['message']) ?></strong>
                                        <br><small class="text-muted">Saran: <?= htmlspecialchars($rec['action']) ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <a href="analytics.php?export=1" class="btn btn-success"><i class="fas fa-file-excel"></i> Download Report (Excel)</a>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Time Series Chart (sesuai ketentuan) -->
                        <div class="col-xl-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-area me-1"></i>
                                    Time Series - Trend Event dan Peserta (6 Bulan Terakhir)
                                </div>
                                <div class="card-body">
                                    <canvas id="timeSeriesChart" width="100%" height="60"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pie Chart (sesuai ketentuan) -->
                        <div class="col-xl-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-pie me-1"></i>
                                    Kategori Event Populer
                                </div>
                                <div class="card-body">
                                    <canvas id="categoryPieChart" width="100%" height="60"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Charts Row -->
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

                    <!-- Most Popular Events -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-fire me-1"></i>
                                    Most Popular Events
                                </div>
                                <div class="card-body">
                                    <canvas id="mostPopularChart" width="100%" height="60"></canvas>

                                    <div class="table-responsive mt-3">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;">#</th>
                                                    <th>Event</th>
                                                    <th style="width:120px;">Participants</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $rank = 1; foreach ($top_events as $te): ?>
                                                <tr>
                                                    <td><?= $rank++ ?></td>
                                                    <td><?= htmlspecialchars($te['title']) ?></td>
                                                    <td><?= (int)$te['participant_count'] ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="footer py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright © Event Management 2024</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        // Time Series Chart (sesuai ketentuan)
        const timeSeriesCtx = document.getElementById('timeSeriesChart').getContext('2d');
        const timeSeriesLabels = <?php echo json_encode(array_column($event_trend ?? [], 'period_name')); ?>;
        const timeSeriesEvents = <?php echo json_encode(array_column($event_trend ?? [], 'events_count')); ?>;
        const timeSeriesParticipants = <?php echo json_encode(array_column($event_trend ?? [], 'total_participants')); ?>;
        
        new Chart(timeSeriesCtx, {
            type: 'line',
            data: {
                labels: timeSeriesLabels,
                datasets: [
                    {
                        label: 'Jumlah Event',
                        data: timeSeriesEvents,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1
                    },
                    {
                        label: 'Total Peserta',
                        data: timeSeriesParticipants,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart untuk Kategori (sesuai ketentuan)
        const categoryPieCtx = document.getElementById('categoryPieChart').getContext('2d');
        const categoryLabels = <?php echo json_encode(array_slice(array_column($category_data ?? [], 'item_name'), 0, 5)); ?>;
        const categoryData = <?php echo json_encode(array_slice(array_column($category_data ?? [], 'participant_count'), 0, 5)); ?>;
        
        new Chart(categoryPieCtx, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

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

        // Most Popular Events Chart (horizontal)
        (function() {
            const mostLabels = <?php echo json_encode(array_column($top_events ?? [], 'title')); ?>;
            const mostData = <?php echo json_encode(array_map('intval', array_column($top_events ?? [], 'participant_count'))); ?>;
            const ctxMost = document.getElementById('mostPopularChart').getContext('2d');
            new Chart(ctxMost, {
                type: 'bar',
                data: {
                    labels: mostLabels,
                    datasets: [{
                        label: 'Participants',
                        data: mostData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)'
                    }]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        })();
    </script>
</body>
</html>