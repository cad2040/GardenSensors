<?php
require_once __DIR__ . '/../includes/config.php';

// Only allow execution from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

try {
    $logger = new Logger();
    
    // Get the absolute path to the cron directory
    $cronDir = __DIR__;
    
    // Define cron jobs
    $cronJobs = [
        // Check alerts every 5 minutes
        "*/5 * * * * php {$cronDir}/check_alerts.php >> {$cronDir}/logs/check_alerts.log 2>&1",
        
        // Clean up old data daily at midnight
        "0 0 * * * php {$cronDir}/cleanup.php >> {$cronDir}/logs/cleanup.log 2>&1",
        
        // Optimize database weekly on Sunday at 2 AM
        "0 2 * * 0 php {$cronDir}/optimize.php >> {$cronDir}/logs/optimize.log 2>&1"
    ];
    
    // Create logs directory if it doesn't exist
    $logsDir = $cronDir . '/logs';
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    // Get current crontab
    $currentCrontab = shell_exec('crontab -l');
    
    // Remove any existing Garden Sensors cron jobs
    $lines = explode("\n", $currentCrontab);
    $filteredLines = array_filter($lines, function($line) use ($cronDir) {
        return strpos($line, $cronDir) === false;
    });
    
    // Add new cron jobs
    $newCrontab = implode("\n", $filteredLines) . "\n\n# Garden Sensors Cron Jobs\n" . implode("\n", $cronJobs) . "\n";
    
    // Write to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'cron');
    file_put_contents($tempFile, $newCrontab);
    
    // Install new crontab
    exec("crontab {$tempFile}", $output, $returnVar);
    
    // Clean up temporary file
    unlink($tempFile);
    
    if ($returnVar !== 0) {
        throw new Exception('Failed to install crontab');
    }
    
    // Log success
    $logger->info("Cron jobs installed successfully", [
        'jobs' => $cronJobs
    ]);
    
    echo "Cron jobs installed successfully:\n";
    foreach ($cronJobs as $job) {
        echo "- {$job}\n";
    }
    
} catch (Exception $e) {
    $logger->error("Failed to install cron jobs", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 