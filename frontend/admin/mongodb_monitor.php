<?php
session_start();
require_once '../backend/config/database.php';
require_once '../backend/services/MongoLogger.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user']['tipo_utente'] !== 'amministratore') {
    header('Location: ../auth/login.php');
    exit();
}

$mongoLogger = new MongoLogger();

// Get various statistics
$recent_activities = $mongoLogger->getUserLogs($_SESSION['user_id'], 100);
$system_logs = $mongoLogger->getSystemLogs(50);
$activity_stats = $mongoLogger->getActivityStats(null, 7); // Last 7 days

// Handle cleanup request
if ($_POST['action'] ?? '' === 'cleanup') {
    $days = intval($_POST['days'] ?? 90);
    $deleted_count = $mongoLogger->cleanOldLogs($days);
    $message = "Cleaned up $deleted_count old log entries.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MongoDB Activity Monitor - BOSTARTER Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .activity-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .log-entry {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .log-level-error { border-left: 4px solid #dc3545; }
        .log-level-warning { border-left: 4px solid #ffc107; }
        .log-level-info { border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar text-white p-4">
                <h3 class="mb-4">
                    <i class="fas fa-database me-2"></i>
                    MongoDB Monitor
                </h3>
                
                <div class="nav flex-column">
                    <a href="../dashboard.php" class="nav-link text-white mb-2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                    <a href="../stats/top_creators.php" class="nav-link text-white mb-2">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistics
                    </a>
                    <a href="#activity" class="nav-link text-white mb-2">
                        <i class="fas fa-list me-2"></i>
                        Activity Logs
                    </a>
                    <a href="#system" class="nav-link text-white mb-2">
                        <i class="fas fa-server me-2"></i>
                        System Logs
                    </a>
                    <a href="#cleanup" class="nav-link text-white mb-2">
                        <i class="fas fa-trash me-2"></i>
                        Cleanup Logs
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>MongoDB Activity Monitor</h2>
                    <div>
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user']['nickname']); ?></span>
                        <a href="../auth/logout.php" class="btn btn-outline-danger ms-3">Logout</a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Activity Statistics -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h4>Activity Statistics (Last 7 Days)</h4>
                        <div class="row">
                            <?php foreach ($activity_stats as $stat): ?>
                                <div class="col-md-3">
                                    <div class="stat-card text-center">
                                        <h3 class="text-primary"><?php echo $stat['count']; ?></h3>
                                        <p class="mb-0"><?php echo ucwords(str_replace('_', ' ', $stat['_id'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent User Activity -->
                <div class="row mb-4" id="activity">
                    <div class="col-md-6">
                        <h4>Recent User Activities</h4>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($recent_activities)): ?>
                                <p class="text-muted">No recent activities found.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($recent_activities, 0, 20) as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo ucwords(str_replace('_', ' ', $activity['action'])); ?></strong>
                                            <small class="text-muted">
                                                <?php echo $activity['timestamp']->toDateTime()->format('Y-m-d H:i:s'); ?>
                                            </small>
                                        </div>
                                        <div class="text-muted">
                                            User ID: <?php echo $activity['user_id']; ?>
                                            <?php if (!empty($activity['data'])): ?>
                                                <br>Data: <?php echo htmlspecialchars(json_encode($activity['data'], JSON_PRETTY_PRINT)); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- System Logs -->
                    <div class="col-md-6" id="system">
                        <h4>System Logs</h4>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($system_logs)): ?>
                                <p class="text-muted">No system logs found.</p>
                            <?php else: ?>
                                <?php foreach ($system_logs as $log): ?>
                                    <div class="log-entry log-level-<?php echo $log['level']; ?>">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                            <small><?php echo $log['timestamp']->toDateTime()->format('Y-m-d H:i:s'); ?></small>
                                        </div>
                                        <?php if (!empty($log['data'])): ?>
                                            <div class="mt-1">
                                                <small><?php echo htmlspecialchars(json_encode($log['data'], JSON_PRETTY_PRINT)); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Log Cleanup -->
                <div class="row" id="cleanup">
                    <div class="col-md-6">
                        <h4>Log Management</h4>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Cleanup Old Logs</h5>
                                <p class="card-text">Remove logs older than specified number of days to maintain database performance.</p>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="cleanup">
                                    <div class="mb-3">
                                        <label for="days" class="form-label">Days to keep</label>
                                        <input type="number" class="form-control" id="days" name="days" value="90" min="1" max="365">
                                        <small class="form-text text-muted">Logs older than this will be deleted</small>
                                    </div>
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to delete old logs?')">
                                        <i class="fas fa-trash me-2"></i>
                                        Cleanup Logs
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>MongoDB Status</h4>
                        <div class="card">
                            <div class="card-body">
                                <?php 
                                try {
                                    $client = new MongoDB\Client("mongodb://localhost:27017");
                                    $admin = $client->admin;
                                    $serverStatus = $admin->command(['serverStatus' => 1]);
                                    echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>MongoDB is running</div>';
                                    echo '<p><strong>Version:</strong> ' . $serverStatus['version'] . '</p>';
                                    echo '<p><strong>Uptime:</strong> ' . gmdate('H:i:s', $serverStatus['uptime']) . '</p>';
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>MongoDB connection failed</div>';
                                    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
