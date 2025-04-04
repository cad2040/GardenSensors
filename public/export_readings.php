<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sensor_readings_' . date('Y-m-d') . '.csv"');

// Create output handle
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header
fputcsv($output, [
    'Sensor Name',
    'Sensor Type',
    'Location',
    'Reading Value',
    'Unit',
    'Timestamp'
]);

try {
    // Initialize database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASSWORD,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    // Get filter parameters
    $sensor_type = isset($_GET['type']) ? $_GET['type'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // Prepare SQL query
    $sql = "
        SELECT 
            s.name as sensor_name,
            s.type as sensor_type,
            s.location,
            sr.reading_value,
            sr.reading_timestamp
        FROM sensor_readings sr
        JOIN sensors s ON sr.sensor_id = s.id
        WHERE sr.reading_timestamp BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    ";
    
    $params = [$start_date, $end_date];
    
    if ($sensor_type) {
        $sql .= " AND s.type = ?";
        $params[] = $sensor_type;
    }
    
    $sql .= " ORDER BY sr.reading_timestamp DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Write data rows
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unit = $row['sensor_type'] === 'temperature' ? '°C' : '%';
        fputcsv($output, [
            $row['sensor_name'],
            ucfirst($row['sensor_type']),
            $row['location'],
            $row['reading_value'],
            $unit,
            $row['reading_timestamp']
        ]);
    }

} catch (PDOException $e) {
    // Write error message to CSV
    fputcsv($output, ['Error: ' . $e->getMessage()]);
} finally {
    // Close the output handle
    fclose($output);
}
?> 