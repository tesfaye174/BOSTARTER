#!/usr/bin/env php
<?php
/**
 * BOSTARTER Email Queue Processor
 * Cron job script for processing queued email notifications
 * 
 * Usage: php process-email-queue.php [--batch-size=50] [--verbose]
 * 
 * Recommended cron schedule:
 * */5 * * * * /usr/bin/php /path/to/bostarter/backend/cron/process-email-queue.php >/dev/null 2>&1
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EmailNotificationService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use BOSTARTER\Services\EmailNotificationService;

// Configuration
$config = [
    'batch_size' => 50,
    'verbose' => false,
    'max_execution_time' => 300, // 5 minutes
    'lock_file' => __DIR__ . '/email-queue.lock'
];

// Parse command line arguments
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

// Set execution time limit
set_time_limit($config['max_execution_time']);

/**
 * Log message with timestamp
 */
function logMessage($message, $verbose = false) {
    global $config;
    if ($verbose && !$config['verbose']) {
        return;
    }
    echo date('Y-m-d H:i:s') . " - " . $message . "\n";
}

/**
 * Check if another instance is running
 */
function checkLock($lockFile) {
    if (file_exists($lockFile)) {
        $pid = file_get_contents($lockFile);
        // Check if process is still running
        if (posix_kill($pid, 0)) {
            logMessage("Another instance is already running (PID: $pid)");
            exit(1);
        } else {
            // Remove stale lock file
            unlink($lockFile);
            logMessage("Removed stale lock file", true);
        }
    }
    
    // Create lock file
    file_put_contents($lockFile, getmypid());
    
    // Remove lock file on exit
    register_shutdown_function(function() use ($lockFile) {
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    });
}

/**
 * Get queue statistics
 */
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
    
    // Check for concurrent execution
    checkLock($config['lock_file']);
    
    // Initialize database and email service
    $database = new Database();
    $db = $database->getConnection();
    $emailService = new EmailNotificationService($db);
    
    // Get initial queue statistics
    $initialStats = getQueueStats($emailService);
    logMessage("Initial queue status - Pending: {$initialStats['pending']}, Processing: {$initialStats['processing']}, Failed: {$initialStats['failed']}", true);
    
    if ($initialStats['pending'] == 0) {
        logMessage("No pending emails to process");
        exit(0);
    }
    
    // Process email queue
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
        
        // Small delay between batches to prevent overwhelming the SMTP server
        if ($result['processed'] > 0) {
            sleep(1);
        }
    }
    
    // Get final queue statistics
    $finalStats = getQueueStats($emailService);
    
    // Summary
    $executionTime = time() - $startTime;
    logMessage("Email queue processing completed");
    logMessage("Execution time: {$executionTime} seconds");
    logMessage("Total processed: $totalProcessed");
    logMessage("Total successful: $totalSuccessful");
    logMessage("Total failed: $totalFailed");
    logMessage("Final queue status - Pending: {$finalStats['pending']}, Processing: {$finalStats['processing']}, Failed: {$finalStats['failed']}");
    
    // Alert if too many failures
    $failureRate = $totalProcessed > 0 ? ($totalFailed / $totalProcessed) * 100 : 0;
    if ($failureRate > 20) {
        logMessage("WARNING: High failure rate detected ({$failureRate}%)");
        
        // You could send an alert email to administrators here
        // or write to a monitoring system
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
