<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

use GardenSensors\Models\PlantCatalog;

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Please log in to access this page'], 401);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $limit = isset($_GET['limit']) ? max(1, min(500, (int) $_GET['limit'])) : 200;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM plant_catalog WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            sendJsonResponse(['success' => false, 'message' => 'Catalog entry not found'], 404);
        }
        $defaults = class_exists(PlantCatalog::class)
            ? PlantCatalog::mapRowToPlantAttributes($row)
            : $row;
        sendJsonResponse([
            'success' => true,
            'data' => $row,
            'plant_defaults' => $defaults,
        ]);
    }

    $sql = "
        SELECT id, common_name, scientific_name, category,
               min_soil_moisture, max_soil_moisture,
               min_temperature, max_temperature,
               watering_frequency_hours, water_requirement,
               nutrient_level, sunlight, source
        FROM plant_catalog
        WHERE is_active = 1
    ";
    $params = [];
    if ($search !== '') {
        $sql .= ' AND (common_name LIKE ? OR scientific_name LIKE ? OR category LIKE ?)';
        $term = '%' . $search . '%';
        $params = [$term, $term, $term];
    }
    $sql .= ' ORDER BY common_name ASC LIMIT ' . (int) $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->query('SELECT COUNT(*) FROM plant_catalog WHERE is_active = 1');
    $totalActive = (int) $countStmt->fetchColumn();

    sendJsonResponse([
        'success' => true,
        'count' => count($rows),
        'total_active' => $totalActive,
        'data' => $rows,
    ]);
} catch (Throwable $e) {
    if (function_exists('logError')) {
        logError('get_plant_catalog failed: ' . $e->getMessage());
    }
    // Table may not exist yet on partially migrated installs
    sendJsonResponse([
        'success' => true,
        'count' => 0,
        'total_active' => 0,
        'data' => [],
        'message' => 'Plant catalog unavailable',
    ]);
}
