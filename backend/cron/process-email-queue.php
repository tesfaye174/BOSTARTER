<?php
#!/usr/bin/env php
<?php
5 * * * * /usr/bin/php /path/to/process-email-queue.php >/dev/null 2>&1
 */
if (php_sapi_name() !== 'cli') {
    die('CLI only');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailNotificationService.php';
use BOSTARTER\Services\EmailNotificationService;
$config = [
    'batch_size' => (int)($argv[1] ?? 50),
    'verbose' => in_array('--verbose', $argv),
    'max_time' => 300,
    'lock_file' => __DIR__ . '/email-queue.lock'
];
set_time_limit($config['max_time']);
function log_msg($msg, $verbose = false) {
    global $config;
    if ($verbose && !$config['verbose']) return;
    echo date('Y-m-d H:i:s') . " - $msg\n";
}
function check_lock($file) {
    if (file_exists($file)) {
        $pid = file_get_contents($file);
        if (function_exists('posix_kill') && posix_kill($pid, 0)) {
            log_msg("Already running (PID: $pid)");
            exit(1);
        }
        unlink($file);
    }
    file_put_contents($file, getmypid());
    register_shutdown_function(fn() => file_exists($file) && unlink($file));
}
try {
    log_msg("Starting email queue processor");
    check_lock($config['lock_file']);
    $database = new Database();
    $emailService = new EmailNotificationService($database->getConnection());
    $stats = $emailService->getQueueStatistics();
    $pending = array_sum(array_column($stats, 'count'));
    if ($pending == 0) {
        log_msg("No emails to process");
        exit(0);
    }
    log_msg("Processing $pending emails", true);
    $start = time();
    $total = ['processed' => 0, 'successful' => 0, 'failed' => 0];
    while ((time() - $start) < $config['max_time']) {
        $result = $emailService->processEmailQueue($config['batch_size']);
        if (!$result || $result['processed'] == 0) break;
        $total['processed'] += $result['processed'];
        $total['successful'] += $result['successful'];
        $total['failed'] += $result['failed'];
        log_msg("Batch: {$result['processed']} processed, {$result['successful']} sent, {$result['failed']} failed", true);
        if ($result['processed'] > 0) sleep(1);
    }
    $time = time() - $start;
    log_msg("Completed in {$time}s - Processed: {$total['processed']}, Sent: {$total['successful']}, Failed: {$total['failed']}");
    if ($total['processed'] > 0 && ($total['failed'] / $total['processed']) > 0.2) {
        log_msg("WARNING: High failure rate detected");
    }
} catch (Throwable $e) {
    log_msg("ERROR: " . $e->getMessage());
    exit(1);
}
log_msg("Finished successfully");#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailNotificationService.php';
require_once __DIR__ . '/../vendor/autoload.php';
use BOSTARTER\Services\EmailNotificationService;
$config = [
    'batch_size' => 50,
    'verbose' => false,
    'max_execution_time' => 300, 
    'lock_file' => __DIR__ . '/email-queue.lock'
];
$options = getopt('', ['batch-size:', 'verbose', 'help']);
if (isset($options['help'])) {
    echo "BOSTARTER Email Queue Processor\n";
    echo "Usage: php process-email-queue.php [options]\n";
    echo "Options:\n";
    echo "  --batch-size=N  Number of emails to process per batch (default: 50)\n";
    echo "  --verbose       Enable verbose output\n";
    echo "  --help          Show this help message\n";
    exit(0);
}
if (isset($options['batch-size'])) {
    $config['batch_size'] = (int)$options['batch-size'];
}
if (isset($options['verbose'])) {
    $config['verbose'] = true;
}
set_time_limit($config['max_execution_time']);
function logMessage($message, $verbose = false) {
    global $config;
    if ($verbose && !$config['verbose']) {
        return;
    }
    echo date('Y-m-d H:i:s') . " - " . $message . "\n";
}
function checkLock($lockFile) {
    if (file_exists($lockFile)) {
        $pid = file_get_contents($lockFile);
        if (posix_kill($pid, 0)) {
            logMessage("Another instance is already running (PID: $pid)");
            exit(1);
        } else {
            unlink($lockFile);
            logMessage("Removed stale lock file", true);
        }
    }
    file_put_contents($lockFile, getmypid());
    register_shutdown_function(function() use ($lockFile) {
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    });
}
function getQueueStats($emailService) {
    $stats = $emailService->getQueueStatistics();
    $summary = [
        'pending' => 0,
        'processing' => 0,
        'failed' => 0,
        'total' => 0
    ];
    foreach ($stats as $stat) {
        $summary[$stat['status']] = ($summary[$stat['status']] ?? 0) + $stat['count'];
        $summary['total'] += $stat['count'];
    }
    return $summary;
}
try {
    logMessage("Starting email queue processor");
    checkLock($config['lock_file']);
    $database = new Database();
    $db = $database->getConnection();
    $emailService = new EmailNotificationService($db);
    $initialStats = getQueueStats($emailService);
    logMessage("Initial queue status - Pending: {$initialStats['pending']}, Processing: {$initialStats['processing']}, Failed: {$initialStats['failed']}", true);
    if ($initialStats['pending'] == 0) {
        logMessage("No pending emails to process");
        exit(0);
    }
    $startTime = time();
    $totalProcessed = 0;
    $totalSuccessful = 0;
    $totalFailed = 0;
    $batchCount = 0;
    while (time() - $startTime < $config['max_execution_time']) {
        $batchCount++;
        logMessage("Processing batch $batchCount (size: {$config['batch_size']})", true);
        $result = $emailService->processEmailQueue($config['batch_size']);
        if (!$result) {
            logMessage("Error processing email queue");
            break;
        }
        if ($result['processed'] == 0) {
            logMessage("No more emails to process");
            break;
        }
        $totalProcessed += $result['processed'];
        $totalSuccessful += $result['successful'];
        $totalFailed += $result['failed'];
        logMessage("Batch $batchCount completed - Processed: {$result['processed']}, Successful: {$result['successful']}, Failed: {$result['failed']}", true);
        if ($result['processed'] > 0) {
            sleep(1);
        }
    }
    $finalStats = getQueueStats($emailService);
    $executionTime = time() - $startTime;
    logMessage("Email queue processing completed");
    logMessage("Execution time: {$executionTime} seconds");
    logMessage("Total processed: $totalProcessed");
    logMessage("Total successful: $totalSuccessful");
    logMessage("Total failed: $totalFailed");
    logMessage("Final queue status - Pending: {$finalStats['pending']}, Processing: {$finalStats['processing']}, Failed: {$finalStats['failed']}");
    $failureRate = $totalProcessed > 0 ? ($totalFailed / $totalProcessed) * 100 : 0;
    if ($failureRate > 20) {
        logMessage("WARNING: High failure rate detected ({$failureRate}%)");
    }
} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
} catch (Error $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
logMessage("Email queue processor finished successfully");
exit(0);
