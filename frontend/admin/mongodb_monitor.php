<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';
require_once __DIR__ . '/../../backend/utils/FrontendSecurity.php';

// Initialize secure session
SecurityMiddleware::initialize();

// Set security headers
FrontendSecurity::setSecurityHeaders();

// Verify admin role
FrontendSecurity::requireRole('admin');

// Generate CSRF token
$csrf_token = FrontendSecurity::getCSRFToken();

try {
    $mongoLogger = new MongoLogger();
    $error = '';
    $success = '';

    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!FrontendSecurity::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }

        // Rate limiting check
        if (!FrontendSecurity::checkRateLimit('mongodb_actions', 10, 60)) {
            throw new Exception('Too many actions. Please wait a minute.');
        }

        switch ($_POST['action'] ?? '') {
            case 'cleanup':
                $days = filter_input(INPUT_POST, 'days', FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 365]
                ]);
                
                if (!$days) {
                    throw new Exception('Invalid number of days');
                }
                
                $deleted_count = $mongoLogger->cleanOldLogs($days);
                $success = "Successfully cleaned up $deleted_count old log entries";
                break;

            case 'export':
                $format = $_POST['format'] ?? 'json';
                $startDate = $_POST['start_date'] ?? null;
                $endDate = $_POST['end_date'] ?? null;
                
                if ($startDate && !strtotime($startDate)) {
                    throw new Exception('Invalid start date');
                }
                if ($endDate && !strtotime($endDate)) {
                    throw new Exception('Invalid end date');
                }
                
                $logs = $mongoLogger->exportLogs($startDate, $endDate);
                
                switch ($format) {
                    case 'json':
                        header('Content-Type: application/json');
                        header('Content-Disposition: attachment; filename="logs.json"');
                        echo json_encode($logs, JSON_PRETTY_PRINT);
                        exit;
                    
                    case 'csv':
                        header('Content-Type: text/csv');
                        header('Content-Disposition: attachment; filename="logs.csv"');
                        $output = fopen('php://output', 'w');
                        fputcsv($output, ['Timestamp', 'Type', 'User', 'Action', 'Details']);
                        foreach ($logs as $log) {
                            fputcsv($output, [
                                $log['timestamp'],
                                $log['type'],
                                $log['user_id'] ?? 'System',
                                $log['action'],
                                json_encode($log['details'] ?? [])
                            ]);
                        }
                        fclose($output);
                        exit;
                    
                    default:
                        throw new Exception('Invalid export format');
                }
                break;
        }
    }

    // Get monitoring data
    $stats = [
        'recent' => $mongoLogger->getUserLogs($_SESSION['user_id'], 50),
        'system' => $mongoLogger->getSystemLogs(30),
        'activity' => $mongoLogger->getActivityStats(null, 7),
        'performance' => $mongoLogger->getPerformanceMetrics(24), // Last 24 hours
        'errors' => $mongoLogger->getErrorStats(7), // Last 7 days
        'storage' => $mongoLogger->getStorageStats()
    ];

    // Aggregate statistics
    $aggregated = [
        'total_logs' => $mongoLogger->getTotalLogsCount(),
        'today_logs' => $mongoLogger->getTodayLogsCount(),
        'error_rate' => $mongoLogger->getErrorRate(24), // Last 24 hours
        'avg_response_time' => $mongoLogger->getAverageResponseTime(24),
        'peak_hours' => $mongoLogger->getPeakActivityHours(7)
    ];

} catch (Exception $e) {
    $error = $e->getMessage();
    
    // Log the error
    if (isset($mongoLogger)) {
        $mongoLogger->logError('mongodb_monitor_error', [
            'error' => $e->getMessage(),
            'admin_id' => $_SESSION['user_id']
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MongoDB Activity Monitor - BOSTARTER Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-speed: 0.3s;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--primary-gradient);
            padding: 2rem;
            color: white;
        }

        .content {
            padding: 2rem;
            background: #f8f9fa;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform var(--transition-speed);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .activity-log {
            max-height: 600px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 0.5rem;
            transition: transform var(--transition-speed);
        }

        .activity-item:hover {
            transform: translateX(5px);
        }

        .error-log {
            border-left-color: #dc3545;
        }

        .warning-log {
            border-left-color: #ffc107;
        }

        .success-log {
            border-left-color: #28a745;
        }

        .btn-action {
            transition: all var(--transition-speed);
        }

        .btn-action:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1 class="h4 mb-4">MongoDB Monitor</h1>
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-database me-2"></i>
                    <span>Total Logs: <?php echo number_format($aggregated['total_logs']); ?></span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-clock me-2"></i>
                    <span>Today: <?php echo number_format($aggregated['today_logs']); ?></span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>Error Rate: <?php echo number_format($aggregated['error_rate'], 2); ?>%</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mb-4">
                <h2 class="h6 mb-3">Actions</h2>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="cleanup">
                    <div class="input-group">
                        <input type="number" name="days" class="form-control" value="90" min="1" max="365">
                        <button type="submit" class="btn btn-light btn-action">
                            <i class="fas fa-broom me-2"></i>Cleanup
                        </button>
                    </div>
                </form>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="export">
                    <div class="mb-3">
                        <select name="format" class="form-select mb-2">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                        <input type="date" name="start_date" class="form-control mb-2" placeholder="Start Date">
                        <input type="date" name="end_date" class="form-control mb-2" placeholder="End Date">
                        <button type="submit" class="btn btn-light w-100 btn-action">
                            <i class="fas fa-download me-2"></i>Export Logs
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="h6 text-muted mb-3">Response Time</h3>
                        <h4 class="h3 mb-0"><?php echo number_format($aggregated['avg_response_time'], 2); ?>ms</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="h6 text-muted mb-3">Storage Used</h3>
                        <h4 class="h3 mb-0"><?php echo formatBytes($stats['storage']['size']); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="h6 text-muted mb-3">Peak Hour</h3>
                        <h4 class="h3 mb-0"><?php echo $aggregated['peak_hours'][0]['hour']; ?>:00</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="h6 text-muted mb-3">Active Users</h3>
                        <h4 class="h3 mb-0"><?php echo count(array_unique(array_column($stats['recent'], 'user_id'))); ?></h4>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="chart-container">
                        <h3 class="h5 mb-4">Activity Overview</h3>
                        <div id="activityChart"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-container">
                        <h3 class="h5 mb-4">Error Distribution</h3>
                        <div id="errorChart"></div>
                    </div>
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h3 class="h5 mb-0">Recent Activity</h3>
                        </div>
                        <div class="card-body activity-log">
                            <?php foreach ($stats['recent'] as $activity): ?>
                                <div class="activity-item <?php echo getActivityClass($activity['type']); ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($activity['type']); ?></span>
                                        <small class="text-muted"><?php echo formatTimestamp($activity['timestamp']); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($activity['action']); ?></p>
                                    <?php if (!empty($activity['details'])): ?>
                                        <small class="d-block mt-2 text-muted">
                                            <?php echo htmlspecialchars(json_encode($activity['details'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h3 class="h5 mb-0">System Logs</h3>
                        </div>
                        <div class="card-body activity-log">
                            <?php foreach ($stats['system'] as $log): ?>
                                <div class="activity-item <?php echo getLogClass($log['level']); ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge <?php echo getLogBadgeClass($log['level']); ?>">
                                            <?php echo htmlspecialchars($log['level']); ?>
                                        </span>
                                        <small class="text-muted"><?php echo formatTimestamp($log['timestamp']); ?></small>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($log['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.40.0/dist/apexcharts.min.js"></script>
    <script>
        // Activity Chart
        const activityData = <?php echo json_encode($stats['activity']); ?>;
        const activityOptions = {
            series: [{
                name: 'Activity',
                data: activityData.map(item => item.count)
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            stroke: {
                curve: 'smooth'
            },
            xaxis: {
                categories: activityData.map(item => item.date)
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + " actions"
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#activityChart"), activityOptions).render();

        // Error Distribution Chart
        const errorData = <?php echo json_encode($stats['errors']); ?>;
        const errorOptions = {
            series: errorData.map(item => item.count),
            chart: {
                type: 'donut',
                height: 350
            },
            labels: errorData.map(item => item.type),
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };
        new ApexCharts(document.querySelector("#errorChart"), errorOptions).render();
    </script>
</body>
</html>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function formatTimestamp($timestamp) {
    return date('Y-m-d H:i:s', strtotime($timestamp));
}

function getActivityClass($type) {
    return match ($type) {
        'error' => 'error-log',
        'warning' => 'warning-log',
        'success' => 'success-log',
        default => ''
    };
}

function getLogClass($level) {
    return match (strtolower($level)) {
        'error' => 'error-log',
        'warning' => 'warning-log',
        'info' => 'success-log',
        default => ''
    };
}

function getLogBadgeClass($level) {
    return match (strtolower($level)) {
        'error' => 'bg-danger',
        'warning' => 'bg-warning',
        'info' => 'bg-info',
        default => 'bg-secondary'
    };
}
