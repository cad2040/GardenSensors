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
    
    // Optimize tables
    $tables = [
        'readings',
        'notifications',
        'system_logs',
        'rate_limits',
        'sensors',
        'plants',
        'users',
        'user_settings'
    ];
    
    $results = [];
    foreach ($tables as $table) {
        // Analyze table
        $sql = "ANALYZE TABLE {$table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results[$table]['analyze'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Optimize table
        $sql = "OPTIMIZE TABLE {$table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results[$table]['optimize'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check table
        $sql = "CHECK TABLE {$table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results[$table]['check'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create or update indexes
    $indexes = [
        'readings' => [
            'CREATE INDEX IF NOT EXISTS idx_readings_timestamp ON readings (timestamp)',
            'CREATE INDEX IF NOT EXISTS idx_readings_sensor_id ON readings (sensor_id)',
            'CREATE INDEX IF NOT EXISTS idx_readings_user_id ON readings (user_id)'
        ],
        'notifications' => [
            'CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications (user_id)',
            'CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications (created_at)',
            'CREATE INDEX IF NOT EXISTS idx_notifications_read_at ON notifications (read_at)'
        ],
        'system_logs' => [
            'CREATE INDEX IF NOT EXISTS idx_system_logs_user_id ON system_logs (user_id)',
            'CREATE INDEX IF NOT EXISTS idx_system_logs_timestamp ON system_logs (timestamp)',
            'CREATE INDEX IF NOT EXISTS idx_system_logs_type ON system_logs (type)'
        ],
        'rate_limits' => [
            'CREATE INDEX IF NOT EXISTS idx_rate_limits_user_id ON rate_limits (user_id)',
            'CREATE INDEX IF NOT EXISTS idx_rate_limits_timestamp ON rate_limits (timestamp)',
            'CREATE INDEX IF NOT EXISTS idx_rate_limits_endpoint ON rate_limits (endpoint)'
        ],
        'sensors' => [
            'CREATE INDEX IF NOT EXISTS idx_sensors_user_id ON sensors (user_id)',
            'CREATE INDEX IF NOT EXISTS idx_sensors_plant_id ON sensors (plant_id)',
            'CREATE INDEX IF NOT EXISTS idx_sensors_status ON sensors (status)'
        ],
        'plants' => [
            'CREATE INDEX IF NOT EXISTS idx_plants_user_id ON plants (user_id)',
            'CREATE INDEX IF NOT EXISTS idx_plants_type ON plants (type)'
        ]
    ];
    
    foreach ($indexes as $table => $tableIndexes) {
        foreach ($tableIndexes as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Log optimization results
    $logger->info("Database optimization completed", [
        'tables' => $results,
        'indexes_created' => array_sum(array_map('count', $indexes))
    ]);
    
    echo "Database optimization completed successfully:\n";
    foreach ($results as $table => $result) {
        echo "\nTable: {$table}\n";
        echo "Analyze: {$result['analyze']['Msg_text']}\n";
        echo "Optimize: {$result['optimize']['Msg_text']}\n";
        echo "Check: {$result['check']['Msg_text']}\n";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $logger->error("Database optimization failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 