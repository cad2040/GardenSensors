<?php
/**
 * API endpoint for per-plant latest readings and ideal benchmarks.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use GardenSensors\Core\Database;
use GardenSensors\Services\EcNutrientService;

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

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $userId = (int)$_SESSION['user_id'];
    $baseline = 0.3;
    try {
        $settingsStmt = $pdo->prepare('SELECT settings FROM user_settings WHERE user_id = ?');
        $settingsStmt->execute([$userId]);
        $settingsRow = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        if ($settingsRow && $settingsRow['settings']) {
            $userSettings = json_decode($settingsRow['settings'], true) ?: [];
            if (isset($userSettings['baseline_water_ec'])) {
                $baseline = (float)$userSettings['baseline_water_ec'];
            }
        }
    } catch (Throwable $settingsError) {
        // Missing user_settings table or row: keep default baseline
    }

    $sql = "
        SELECT
            p.id AS plant_id,
            p.name AS plant_name,
            p.species,
            p.min_temperature,
            p.max_temperature,
            p.min_soil_moisture,
            p.max_soil_moisture,
            p.ec_underfed,
            p.ec_balanced_min,
            p.ec_balanced_max,
            p.ec_overfed,
            s.id AS sensor_id,
            s.type AS sensor_type,
            r.value AS latest_value,
            r.unit,
            r.created_at AS reading_timestamp
        FROM plants p
        LEFT JOIN plant_sensors ps ON ps.plant_id = p.id
        LEFT JOIN sensors s
            ON s.id = ps.sensor_id
            AND s.type IN ('temperature', 'moisture', 'ec')
        LEFT JOIN readings r
            ON r.sensor_id = s.id
            AND r.id = (
                SELECT r2.id
                FROM readings r2
                WHERE r2.sensor_id = s.id
                ORDER BY r2.created_at DESC, r2.id DESC
                LIMIT 1
            )
        WHERE p.status = 'active'
          AND p.user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if ($plantId !== null && $plantId > 0) {
        $sql .= " AND p.id = :plant_id ";
        $params[':plant_id'] = $plantId;
    }

    $sql .= " ORDER BY p.name, FIELD(s.type, 'temperature', 'moisture', 'ec') ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ecService = new EcNutrientService(new Database($pdo));

    $plants = [];
    foreach ($rows as $row) {
        $id = (int)$row['plant_id'];
        if (!isset($plants[$id])) {
            $plants[$id] = [
                'id' => $id,
                'name' => $row['plant_name'],
                'species' => $row['species'],
                'metrics' => [],
            ];
        }

        $type = $row['sensor_type'];
        if ($type === null) {
            continue;
        }

        if ($type === 'temperature') {
            $latest = $row['latest_value'] !== null ? (float)$row['latest_value'] : null;
            $idealMin = $row['min_temperature'] !== null ? (float)$row['min_temperature'] : null;
            $idealMax = $row['max_temperature'] !== null ? (float)$row['max_temperature'] : null;
            $status = 'no-data';
            if ($latest !== null && $idealMin !== null && $idealMax !== null) {
                $status = $latest < $idealMin ? 'low' : ($latest > $idealMax ? 'high' : 'ideal');
            }
            $plants[$id]['metrics']['temperature'] = [
                'label' => 'Soil temperature',
                'value' => $latest,
                'unit' => $row['unit'] ?: '°C',
                'timestamp' => $row['reading_timestamp'],
                'ideal_min' => $idealMin,
                'ideal_max' => $idealMax,
                'ideal_value' => $idealMin !== null && $idealMax !== null
                    ? round(($idealMin + $idealMax) / 2, 1) : null,
                'status' => $status,
            ];
            continue;
        }

        if ($type === 'moisture') {
            $latest = $row['latest_value'] !== null ? (float)$row['latest_value'] : null;
            $idealMin = $row['min_soil_moisture'] !== null ? (float)$row['min_soil_moisture'] : null;
            $idealMax = $row['max_soil_moisture'] !== null ? (float)$row['max_soil_moisture'] : null;
            $status = 'no-data';
            if ($latest !== null && $idealMin !== null && $idealMax !== null) {
                $status = $latest < $idealMin ? 'low' : ($latest > $idealMax ? 'high' : 'ideal');
            }
            $plants[$id]['metrics']['moisture'] = [
                'label' => 'Soil moisture',
                'value' => $latest,
                'unit' => $row['unit'] ?: '%',
                'timestamp' => $row['reading_timestamp'],
                'ideal_min' => $idealMin,
                'ideal_max' => $idealMax,
                'ideal_value' => $idealMin !== null && $idealMax !== null
                    ? round(($idealMin + $idealMax) / 2, 1) : null,
                'status' => $status,
            ];
            continue;
        }

        if ($type === 'ec' && $row['sensor_id']) {
            $zones = [
                'ec_underfed' => (float)($row['ec_underfed'] ?? 0.80),
                'ec_balanced_min' => (float)($row['ec_balanced_min'] ?? 1.00),
                'ec_balanced_max' => (float)($row['ec_balanced_max'] ?? 1.60),
                'ec_overfed' => (float)($row['ec_overfed'] ?? 2.00),
            ];
            $soilEc = $row['latest_value'] !== null ? (float)$row['latest_value'] : null;
            if ($soilEc === null) {
                $plants[$id]['metrics']['ec'] = [
                    'label' => 'Soil nutrient EC',
                    'value' => null,
                    'available_ec' => null,
                    'unit' => 'mS/cm',
                    'timestamp' => null,
                    'ideal_min' => $zones['ec_balanced_min'],
                    'ideal_max' => $zones['ec_balanced_max'],
                    'ideal_value' => round(($zones['ec_balanced_min'] + $zones['ec_balanced_max']) / 2, 2),
                    'status' => 'no-data',
                    'nutrient_state' => null,
                ];
                continue;
            }

            $eval = $ecService->evaluateSensor((int)$row['sensor_id'], $soilEc, $baseline, $zones);
            $plants[$id]['metrics']['ec'] = [
                'label' => 'Soil nutrient EC',
                'value' => $eval['available_ec'],
                'soil_ec' => $eval['soil_ec'],
                'available_ec' => $eval['available_ec'],
                'unit' => 'mS/cm',
                'timestamp' => $row['reading_timestamp'],
                'ideal_min' => $zones['ec_balanced_min'],
                'ideal_max' => $zones['ec_balanced_max'],
                'ideal_value' => round(($zones['ec_balanced_min'] + $zones['ec_balanced_max']) / 2, 2),
                'status' => $ecService->dashboardStatusFromState($eval['state']),
                'nutrient_state' => $eval['state'],
                'trend_slope' => $eval['slope'],
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'plants' => array_values($plants),
        'baseline_water_ec' => $baseline,
        'ec_note' => 'Nutrient EC is soil EC minus your tap-water baseline. It tracks dissolved salts as a fertilizer proxy—not isolated nitrate. Calibrate baseline with a cup of tap water.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load summary',
        'details' => $e->getMessage()
    ]);
}
