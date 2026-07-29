<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : '24h';

$rangeMap = [
    '1h' => '-1 hour',
    '24h' => '-24 hours',
    '7d' => '-7 days',
    '30d' => '-30 days',
];
if (!isset($rangeMap[$timeRange])) {
    $timeRange = '24h';
}
$startTime = date('Y-m-d H:i:s', strtotime($rangeMap[$timeRange]));

try {
    $db = new Database();
    $conn = $db->getConnection();

    $query = "
        SELECT 
            r.id,
            r.sensor_id,
            r.value,
            r.unit,
            r.temperature,
            r.humidity,
            r.created_at as timestamp,
            s.type,
            s.name as sensor_name
        FROM readings r
        JOIN sensors s ON r.sensor_id = s.id
        WHERE (s.user_id = :user_id OR s.user_id IS NULL)
          AND r.created_at >= :start_time
    ";
    $params = [
        ':user_id' => $_SESSION['user_id'],
        ':start_time' => $startTime,
    ];

    if ($sensorId) {
        $query .= " AND r.sensor_id = :sensor_id";
        $params[':sensor_id'] = $sensorId;
    }

    $query .= " ORDER BY r.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'readings' => $readings
    ]);
} catch (Exception $e) {
    logError('Error fetching readings: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching readings'
    ]);
}
