<?php
require_once 'config.php';
require_once 'includes/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Database connection successful!\n";
    
    // Test query
    $query = "SELECT 1";
    $stmt = $conn->query($query);
    echo "Test query successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 