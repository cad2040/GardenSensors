-- Canonical deterministic dashboard seed (EC nutrient proxy).
-- Soil EC in mS/cm; available EC ≈ soil_ec - user baseline (default 0.3 in app).

INSERT INTO plants (
    name, species, description, location,
    min_soil_moisture, max_soil_moisture, watering_frequency,
    min_temperature, max_temperature,
    min_ph, max_ph,
    ec_underfed, ec_balanced_min, ec_balanced_max, ec_overfed,
    status, user_id, created_at, updated_at
) VALUES
    ('Tomato Plant', 'Solanum lycopersicum', 'Seeded tomato for dashboard testing', 'Garden Bed 1',
     40, 80, 24, 18.0, 27.0, 6.0, 6.8, 0.80, 1.00, 1.60, 2.00, 'active', 1, NOW(), NOW()),
    ('Basil Plant', 'Ocimum basilicum', 'Seeded basil for dashboard testing', 'Garden Bed 2',
     35, 70, 12, 18.0, 27.0, 6.0, 7.5, 0.80, 1.00, 1.60, 2.00, 'active', 1, NOW(), NOW()),
    ('Lettuce Plant', 'Lactuca sativa', 'Seeded lettuce for dashboard testing', 'Garden Bed 3',
     45, 75, 18, 10.0, 21.0, 6.0, 7.0, 0.80, 1.00, 1.60, 2.00, 'active', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    species = VALUES(species),
    description = VALUES(description),
    location = VALUES(location),
    min_soil_moisture = VALUES(min_soil_moisture),
    max_soil_moisture = VALUES(max_soil_moisture),
    watering_frequency = VALUES(watering_frequency),
    min_temperature = VALUES(min_temperature),
    max_temperature = VALUES(max_temperature),
    min_ph = VALUES(min_ph),
    max_ph = VALUES(max_ph),
    ec_underfed = VALUES(ec_underfed),
    ec_balanced_min = VALUES(ec_balanced_min),
    ec_balanced_max = VALUES(ec_balanced_max),
    ec_overfed = VALUES(ec_overfed),
    status = 'active',
    user_id = 1,
    updated_at = NOW();

DELETE FROM sensors WHERE name IN (
    'Temperature Sensor 1', 'Humidity Sensor 1', 'Soil Moisture 1',
    'Tomato Temperature Sensor', 'Tomato Humidity Sensor', 'Tomato Moisture Sensor',
    'Tomato pH Sensor', 'Tomato Nitrogen Sensor', 'Tomato EC Sensor',
    'Basil Temperature Sensor', 'Basil Humidity Sensor', 'Basil Moisture Sensor',
    'Basil pH Sensor', 'Basil Nitrogen Sensor', 'Basil EC Sensor',
    'Lettuce Temperature Sensor', 'Lettuce Humidity Sensor', 'Lettuce Moisture Sensor',
    'Lettuce pH Sensor', 'Lettuce Nitrogen Sensor', 'Lettuce EC Sensor'
);

INSERT INTO sensors (
    name, type, location, description, plot_type, unit,
    status, user_id, created_at, updated_at
) VALUES
    ('Tomato Temperature Sensor', 'temperature', 'Garden Bed 1', 'Seeded soil temperature', 'temperature', '°C', 'active', 1, NOW(), NOW()),
    ('Tomato Moisture Sensor', 'moisture', 'Garden Bed 1', 'Seeded soil moisture', 'moisture', '%', 'active', 1, NOW(), NOW()),
    ('Tomato pH Sensor', 'ph', 'Garden Bed 1', 'Seeded soil pH', 'ph', 'pH', 'active', 1, NOW(), NOW()),
    ('Tomato EC Sensor', 'ec', 'Garden Bed 1', 'Seeded soil EC nutrient proxy', 'ec', 'mS/cm', 'active', 1, NOW(), NOW()),
    ('Basil Temperature Sensor', 'temperature', 'Garden Bed 2', 'Seeded soil temperature', 'temperature', '°C', 'active', 1, NOW(), NOW()),
    ('Basil Moisture Sensor', 'moisture', 'Garden Bed 2', 'Seeded soil moisture', 'moisture', '%', 'active', 1, NOW(), NOW()),
    ('Basil pH Sensor', 'ph', 'Garden Bed 2', 'Seeded soil pH', 'ph', 'pH', 'active', 1, NOW(), NOW()),
    ('Basil EC Sensor', 'ec', 'Garden Bed 2', 'Seeded soil EC nutrient proxy', 'ec', 'mS/cm', 'active', 1, NOW(), NOW()),
    ('Lettuce Temperature Sensor', 'temperature', 'Garden Bed 3', 'Seeded soil temperature', 'temperature', '°C', 'active', 1, NOW(), NOW()),
    ('Lettuce Moisture Sensor', 'moisture', 'Garden Bed 3', 'Seeded soil moisture', 'moisture', '%', 'active', 1, NOW(), NOW()),
    ('Lettuce pH Sensor', 'ph', 'Garden Bed 3', 'Seeded soil pH', 'ph', 'pH', 'active', 1, NOW(), NOW()),
    ('Lettuce EC Sensor', 'ec', 'Garden Bed 3', 'Seeded soil EC nutrient proxy', 'ec', 'mS/cm', 'active', 1, NOW(), NOW());

INSERT INTO plant_sensors (plant_id, sensor_id, water_amount, next_watering, created_at, updated_at)
SELECT p.id, s.id, 100, DATE_ADD(NOW(), INTERVAL p.watering_frequency HOUR), NOW(), NOW()
FROM plants p
JOIN sensors s ON s.name LIKE CONCAT(REPLACE(p.name, ' Plant', ''), ' % Sensor')
WHERE p.name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant');

INSERT INTO readings (sensor_id, value, unit, created_at)
SELECT s.id, v.value, s.unit, DATE_SUB(NOW(), INTERVAL v.days_ago DAY)
FROM sensors s
JOIN (
    SELECT 'Tomato Temperature Sensor' sensor_name, 6 days_ago, 22.0 value UNION ALL
    SELECT 'Tomato Temperature Sensor', 3, 23.5 UNION ALL SELECT 'Tomato Temperature Sensor', 0, 24.0 UNION ALL
    SELECT 'Tomato Moisture Sensor', 6, 52.0 UNION ALL SELECT 'Tomato Moisture Sensor', 3, 56.0 UNION ALL SELECT 'Tomato Moisture Sensor', 0, 58.0 UNION ALL
    SELECT 'Tomato pH Sensor', 6, 6.2 UNION ALL SELECT 'Tomato pH Sensor', 3, 6.4 UNION ALL SELECT 'Tomato pH Sensor', 0, 6.5 UNION ALL
    SELECT 'Tomato EC Sensor', 6, 1.40 UNION ALL SELECT 'Tomato EC Sensor', 3, 1.50 UNION ALL SELECT 'Tomato EC Sensor', 0, 1.45 UNION ALL
    SELECT 'Basil Temperature Sensor', 6, 21.0 UNION ALL SELECT 'Basil Temperature Sensor', 3, 22.0 UNION ALL SELECT 'Basil Temperature Sensor', 0, 23.0 UNION ALL
    SELECT 'Basil Moisture Sensor', 6, 48.0 UNION ALL SELECT 'Basil Moisture Sensor', 3, 51.0 UNION ALL SELECT 'Basil Moisture Sensor', 0, 54.0 UNION ALL
    SELECT 'Basil pH Sensor', 6, 6.5 UNION ALL SELECT 'Basil pH Sensor', 3, 6.7 UNION ALL SELECT 'Basil pH Sensor', 0, 6.8 UNION ALL
    SELECT 'Basil EC Sensor', 6, 1.30 UNION ALL SELECT 'Basil EC Sensor', 3, 1.35 UNION ALL SELECT 'Basil EC Sensor', 0, 1.32 UNION ALL
    SELECT 'Lettuce Temperature Sensor', 6, 16.0 UNION ALL SELECT 'Lettuce Temperature Sensor', 3, 17.0 UNION ALL SELECT 'Lettuce Temperature Sensor', 0, 18.0 UNION ALL
    SELECT 'Lettuce Moisture Sensor', 6, 55.0 UNION ALL SELECT 'Lettuce Moisture Sensor', 3, 59.0 UNION ALL SELECT 'Lettuce Moisture Sensor', 0, 62.0 UNION ALL
    SELECT 'Lettuce pH Sensor', 6, 6.1 UNION ALL SELECT 'Lettuce pH Sensor', 3, 6.3 UNION ALL SELECT 'Lettuce pH Sensor', 0, 6.4 UNION ALL
    SELECT 'Lettuce EC Sensor', 6, 1.50 UNION ALL SELECT 'Lettuce EC Sensor', 3, 1.30 UNION ALL SELECT 'Lettuce EC Sensor', 0, 1.00
) v ON v.sensor_name = s.name;

UPDATE sensors s
SET s.last_reading = (
        SELECT r.value FROM readings r
        WHERE r.sensor_id = s.id
        ORDER BY r.created_at DESC, r.id DESC LIMIT 1
    ),
    s.last_reading_time = (
        SELECT r.created_at FROM readings r
        WHERE r.sensor_id = s.id
        ORDER BY r.created_at DESC, r.id DESC LIMIT 1
    )
WHERE s.name IN (
    'Tomato Temperature Sensor', 'Tomato Moisture Sensor', 'Tomato pH Sensor', 'Tomato EC Sensor',
    'Basil Temperature Sensor', 'Basil Moisture Sensor', 'Basil pH Sensor', 'Basil EC Sensor',
    'Lettuce Temperature Sensor', 'Lettuce Moisture Sensor', 'Lettuce pH Sensor', 'Lettuce EC Sensor'
);

SELECT p.name, s.type, COUNT(r.id) AS reading_count
FROM plants p
JOIN plant_sensors ps ON ps.plant_id = p.id
JOIN sensors s ON s.id = ps.sensor_id
LEFT JOIN readings r ON r.sensor_id = s.id
WHERE p.name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant')
GROUP BY p.name, s.type
ORDER BY p.name, s.type;
