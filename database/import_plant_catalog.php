#!/usr/bin/env php
<?php
/**
 * Import plant catalog rows from CSV.
 *
 * Usage:
 *   php database/import_plant_catalog.php
 *   php database/import_plant_catalog.php --file=database/catalog/plant_catalog.csv
 *   SEED_PLANT_CATALOG=1 php database/import_plant_catalog.php
 *
 * Skips import (exit 0) when the file is missing or has no data rows,
 * unless --require-file is passed.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (is_readable($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$opts = getopt('', ['file::', 'require-file', 'dry-run', 'help']);
if (isset($opts['help'])) {
    fwrite(STDOUT, "Usage: php database/import_plant_catalog.php [--file=path] [--require-file] [--dry-run]\n");
    exit(0);
}

$file = $opts['file']
    ?? getenv('PLANT_CATALOG_FILE')
    ?: ($root . '/database/catalog/plant_catalog.csv');

if (!is_string($file) || $file === '') {
    $file = $root . '/database/catalog/plant_catalog.csv';
}
if ($file[0] !== '/' && !preg_match('#^[A-Za-z]:[\\\\/]#', $file)) {
    $file = $root . '/' . ltrim(str_replace('\\', '/', $file), '/');
}

$requireFile = isset($opts['require-file']);
$dryRun = isset($opts['dry-run']);

if (!is_readable($file)) {
    $msg = "Plant catalog file not found: {$file}";
    if ($requireFile) {
        fwrite(STDERR, $msg . "\n");
        exit(1);
    }
    fwrite(STDOUT, $msg . " — skipping import.\n");
    exit(0);
}

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_ENV['DB_DATABASE'] ?? 'garden_sensors'));
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'garden_sensors');
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'garden_sensors');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$fh = fopen($file, 'r');
if ($fh === false) {
    fwrite(STDERR, "Unable to open {$file}\n");
    exit(1);
}

$header = fgetcsv($fh);
if ($header === false || count($header) < 2) {
    fclose($fh);
    fwrite(STDOUT, "Catalog CSV has no header — skipping.\n");
    exit(0);
}

$header = array_map(static function ($h) {
    return strtolower(trim((string) $h));
}, $header);

$required = ['common_name'];
foreach ($required as $col) {
    if (!in_array($col, $header, true)) {
        fclose($fh);
        fwrite(STDERR, "Catalog CSV missing required column: {$col}\n");
        exit(1);
    }
}

$upsert = $pdo->prepare("
    INSERT INTO plant_catalog (
        external_pid, common_name, scientific_name, category, description,
        min_soil_moisture, max_soil_moisture, min_temperature, max_temperature,
        min_soil_ec, max_soil_ec, min_env_humidity, max_env_humidity,
        preferred_ph_min, preferred_ph_max, water_requirement, nutrient_level,
        sunlight, watering_frequency_hours, source, source_url, is_active
    ) VALUES (
        :external_pid, :common_name, :scientific_name, :category, :description,
        :min_soil_moisture, :max_soil_moisture, :min_temperature, :max_temperature,
        :min_soil_ec, :max_soil_ec, :min_env_humidity, :max_env_humidity,
        :preferred_ph_min, :preferred_ph_max, :water_requirement, :nutrient_level,
        :sunlight, :watering_frequency_hours, :source, :source_url, :is_active
    )
    ON DUPLICATE KEY UPDATE
        common_name = VALUES(common_name),
        scientific_name = VALUES(scientific_name),
        category = VALUES(category),
        description = VALUES(description),
        min_soil_moisture = VALUES(min_soil_moisture),
        max_soil_moisture = VALUES(max_soil_moisture),
        min_temperature = VALUES(min_temperature),
        max_temperature = VALUES(max_temperature),
        min_soil_ec = VALUES(min_soil_ec),
        max_soil_ec = VALUES(max_soil_ec),
        min_env_humidity = VALUES(min_env_humidity),
        max_env_humidity = VALUES(max_env_humidity),
        preferred_ph_min = VALUES(preferred_ph_min),
        preferred_ph_max = VALUES(preferred_ph_max),
        water_requirement = VALUES(water_requirement),
        nutrient_level = VALUES(nutrient_level),
        sunlight = VALUES(sunlight),
        watering_frequency_hours = VALUES(watering_frequency_hours),
        source_url = VALUES(source_url),
        is_active = VALUES(is_active),
        updated_at = CURRENT_TIMESTAMP
");

$insertOnly = $pdo->prepare("
    INSERT INTO plant_catalog (
        external_pid, common_name, scientific_name, category, description,
        min_soil_moisture, max_soil_moisture, min_temperature, max_temperature,
        min_soil_ec, max_soil_ec, min_env_humidity, max_env_humidity,
        preferred_ph_min, preferred_ph_max, water_requirement, nutrient_level,
        sunlight, watering_frequency_hours, source, source_url, is_active
    ) VALUES (
        :external_pid, :common_name, :scientific_name, :category, :description,
        :min_soil_moisture, :max_soil_moisture, :min_temperature, :max_temperature,
        :min_soil_ec, :max_soil_ec, :min_env_humidity, :max_env_humidity,
        :preferred_ph_min, :preferred_ph_max, :water_requirement, :nutrient_level,
        :sunlight, :watering_frequency_hours, :source, :source_url, :is_active
    )
");

$nullableInt = static function ($v) {
    if ($v === null || $v === '') {
        return null;
    }
    return (int) $v;
};
$nullableFloat = static function ($v) {
    if ($v === null || $v === '') {
        return null;
    }
    return (float) $v;
};
$nullableStr = static function ($v) {
    if ($v === null) {
        return null;
    }
    $s = trim((string) $v);
    return $s === '' ? null : $s;
};

$imported = 0;
$skipped = 0;
$rowNum = 1;

while (($cols = fgetcsv($fh)) !== false) {
    $rowNum++;
    if (count($cols) === 1 && trim((string) $cols[0]) === '') {
        continue;
    }
    // Pad short rows
    while (count($cols) < count($header)) {
        $cols[] = '';
    }
    $row = [];
    foreach ($header as $i => $key) {
        $row[$key] = $cols[$i] ?? '';
    }

    $common = trim((string) ($row['common_name'] ?? ''));
    if ($common === '') {
        $skipped++;
        continue;
    }

    $params = [
        ':external_pid' => $nullableStr($row['external_pid'] ?? null),
        ':common_name' => $common,
        ':scientific_name' => $nullableStr($row['scientific_name'] ?? null),
        ':category' => $nullableStr($row['category'] ?? null),
        ':description' => $nullableStr($row['description'] ?? null),
        ':min_soil_moisture' => $nullableInt($row['min_soil_moisture'] ?? null),
        ':max_soil_moisture' => $nullableInt($row['max_soil_moisture'] ?? null),
        ':min_temperature' => $nullableFloat($row['min_temperature'] ?? null),
        ':max_temperature' => $nullableFloat($row['max_temperature'] ?? null),
        ':min_soil_ec' => $nullableInt($row['min_soil_ec'] ?? null),
        ':max_soil_ec' => $nullableInt($row['max_soil_ec'] ?? null),
        ':min_env_humidity' => $nullableInt($row['min_env_humidity'] ?? null),
        ':max_env_humidity' => $nullableInt($row['max_env_humidity'] ?? null),
        ':preferred_ph_min' => $nullableFloat($row['preferred_ph_min'] ?? null),
        ':preferred_ph_max' => $nullableFloat($row['preferred_ph_max'] ?? null),
        ':water_requirement' => $nullableStr($row['water_requirement'] ?? null),
        ':nutrient_level' => $nullableStr($row['nutrient_level'] ?? null),
        ':sunlight' => $nullableStr($row['sunlight'] ?? null),
        ':watering_frequency_hours' => $nullableInt($row['watering_frequency_hours'] ?? null),
        ':source' => $nullableStr($row['source'] ?? null) ?: 'manual',
        ':source_url' => $nullableStr($row['source_url'] ?? null),
        ':is_active' => isset($row['is_active']) && $row['is_active'] !== ''
            ? (int) ((string) $row['is_active'] === '0' ? 0 : 1)
            : 1,
    ];

    if ($dryRun) {
        $imported++;
        continue;
    }

    try {
        if ($params[':external_pid'] !== null) {
            $upsert->execute($params);
        } else {
            $insertOnly->execute($params);
        }
        $imported++;
    } catch (PDOException $e) {
        fwrite(STDERR, "Row {$rowNum} ({$common}): " . $e->getMessage() . "\n");
        $skipped++;
    }
}

fclose($fh);

$mode = $dryRun ? 'dry-run counted' : 'imported/updated';
fwrite(STDOUT, "Plant catalog {$mode}: {$imported} row(s), skipped {$skipped}.\n");
exit(0);
