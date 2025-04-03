<?php
require_once __DIR__ . '/../includes/config.php';

// Only allow execution from command line or cron
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

try {
    $db = new Database();
    $logger = new Logger();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Clean up old readings (keep last 30 days)
    $sql = "DELETE FROM readings 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $readingsDeleted = $stmt->rowCount();
    
    // Clean up old notifications (keep last 90 days)
    $sql = "DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $notificationsDeleted = $stmt->rowCount();
    
    // Clean up old system logs (keep last 180 days)
    $sql = "DELETE FROM system_logs 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 180 DAY)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $logsDeleted = $stmt->rowCount();
    
    // Clean up old rate limit records (keep last 24 hours)
    $sql = "DELETE FROM rate_limits 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rateLimitsDeleted = $stmt->rowCount();
    
    // Clean up old cache files
    $cacheDir = CACHE_DIR;
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        $cacheFilesDeleted = 0;
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= CACHE_TTL) {
                    if (unlink($file)) {
                        $cacheFilesDeleted++;
                    }
                }
            }
        }
    }
    
    // Clean up old log files
    $logDir = dirname(LOG_FILE);
    if (is_dir($logDir)) {
        $files = glob($logDir . '/app_*.log');
        $logFilesDeleted = 0;
        rsort($files);
        
        foreach (array_slice($files, LOG_MAX_FILES) as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $logFilesDeleted++;
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Log cleanup results
    $logger->info("Cleanup completed", [
        'readings_deleted' => $readingsDeleted,
        'notifications_deleted' => $notificationsDeleted,
        'logs_deleted' => $logsDeleted,
        'rate_limits_deleted' => $rateLimitsDeleted,
        'cache_files_deleted' => $cacheFilesDeleted ?? 0,
        'log_files_deleted' => $logFilesDeleted ?? 0
    ]);
    
    echo "Cleanup completed successfully:\n";
    echo "- {$readingsDeleted} old readings deleted\n";
    echo "- {$notificationsDeleted} old notifications deleted\n";
    echo "- {$logsDeleted} old system logs deleted\n";
    echo "- {$rateLimitsDeleted} old rate limit records deleted\n";
    echo "- " . ($cacheFilesDeleted ?? 0) . " old cache files deleted\n";
    echo "- " . ($logFilesDeleted ?? 0) . " old log files deleted\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $logger->error("Cleanup failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 