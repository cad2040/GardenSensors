-- Docker init seed (runs after schema + view)
-- Schema already inserts Tomato/Basil/Succulent without user_id; attach them to admin
-- and add Lettuce + per-plant metric sensors/readings for dashboard parity.

UPDATE plants SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;
UPDATE sensors SET user_id = 1 WHERE user_id IS NULL OR user_id = 0;

INSERT INTO plants (name, species, location, min_soil_moisture, max_soil_moisture, watering_frequency, status, user_id)
SELECT 'Lettuce Plant', 'Lactuca sativa', 'Greenhouse 2', 45, 75, 18, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM plants WHERE name = 'Lettuce Plant');

INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Tomato Temperature Sensor', 'temperature', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '°C', 'temperature', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Temperature Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Tomato Humidity Sensor', 'humidity', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '%', 'humidity', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Humidity Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Tomato Moisture Sensor', 'moisture', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '%', 'moisture', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Moisture Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Basil Temperature Sensor', 'temperature', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '°C', 'temperature', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Temperature Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Basil Humidity Sensor', 'humidity', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '%', 'humidity', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Humidity Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Basil Moisture Sensor', 'moisture', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '%', 'moisture', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Moisture Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Lettuce Temperature Sensor', 'temperature', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '°C', 'temperature', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Temperature Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Lettuce Humidity Sensor', 'humidity', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '%', 'humidity', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Humidity Sensor');
INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id, status)
SELECT 'Lettuce Moisture Sensor', 'moisture', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '%', 'moisture', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Moisture Sensor');

INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)
SELECT s.id, p.id, 250 FROM sensors s JOIN plants p ON p.name = 'Tomato Plant'
WHERE s.name IN ('Tomato Temperature Sensor', 'Tomato Humidity Sensor', 'Tomato Moisture Sensor');
INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)
SELECT s.id, p.id, 200 FROM sensors s JOIN plants p ON p.name = 'Basil Plant'
WHERE s.name IN ('Basil Temperature Sensor', 'Basil Humidity Sensor', 'Basil Moisture Sensor');
INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)
SELECT s.id, p.id, 220 FROM sensors s JOIN plants p ON p.name = 'Lettuce Plant'
WHERE s.name IN ('Lettuce Temperature Sensor', 'Lettuce Humidity Sensor', 'Lettuce Moisture Sensor');

DELETE r FROM readings r
JOIN sensors s ON s.id = r.sensor_id
WHERE s.name IN (
  'Tomato Temperature Sensor', 'Tomato Humidity Sensor', 'Tomato Moisture Sensor',
  'Basil Temperature Sensor', 'Basil Humidity Sensor', 'Basil Moisture Sensor',
  'Lettuce Temperature Sensor', 'Lettuce Humidity Sensor', 'Lettuce Moisture Sensor'
);

INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 24.5, '°C', 24.5, 62.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 25.0, '°C', 25.0, 61.5, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 24.2, '°C', 24.2, 63.2, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 62.0, '%', 24.0, 62.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 64.0, '%', 24.8, 64.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 61.0, '%', 24.3, 61.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 55.0, '%', 23.8, 60.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 53.0, '%', 24.6, 62.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 57.0, '%', 24.1, 61.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 22.4, '°C', 22.4, 58.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 23.1, '°C', 23.1, 57.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 22.8, '°C', 22.8, 59.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 58.0, '%', 22.5, 58.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 56.0, '%', 23.0, 56.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 60.0, '%', 22.9, 60.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 49.0, '%', 22.3, 57.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 51.0, '%', 22.8, 58.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 50.0, '%', 22.6, 59.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 21.5, '°C', 21.5, 66.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 21.9, '°C', 21.9, 65.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 22.2, '°C', 22.2, 64.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 66.0, '%', 21.6, 66.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 65.0, '%', 22.0, 65.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 64.0, '%', 22.1, 64.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 47.0, '%', 21.3, 65.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 45.0, '%', 21.8, 64.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';
INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)
SELECT s.id, 48.0, '%', 22.0, 63.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';
