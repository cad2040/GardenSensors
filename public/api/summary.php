<?php
/**
 * API endpoint for dashboard summary averages
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$plantId = null;
if (isset($_GET['plant_id']) && $_GET['plant_id'] !== '' && $_GET['plant_id'] !== '0') {
    $plantId = (int)$_GET['plant_id'];
}
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
if ($days < 1 || $days > 365) {
    $days = 7;
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sql = "
        SELECT s.type AS sensor_type, AVG(r.value) AS avg_value
        FROM readings r
        JOIN sensors s ON s.id = r.sensor_id
        LEFT JOIN plant_sensors ps ON ps.sensor_id = s.id
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
    ";
    $params = [':days' => $days];

    if ($plantId !== null && $plantId > 0) {
        $sql .= " AND ps.plant_id = :plant_id ";
        $params[':plant_id'] = $plantId;
    }

    $sql .= " GROUP BY s.type ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $averages = [
        'temperature' => 0,
        'humidity' => 0,
        'moisture' => 0
    ];
    foreach ($rows as $row) {
        if (array_key_exists($row['sensor_type'], $averages)) {
            $averages[$row['sensor_type']] = round((float)$row['avg_value'], 1);
        }
    }

    echo json_encode([
        'success' => true,
        'averages' => $averages
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load summary',
        'details' => $e->getMessage()
    ]);
}

