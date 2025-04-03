-- Create database if not exists
CREATE DATABASE IF NOT EXISTS SoilSensors;
USE SoilSensors;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create sensors table
CREATE TABLE IF NOT EXISTS sensors (
    sensor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    type VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create readings table
CREATE TABLE IF NOT EXISTS readings (
    reading_id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    moisture_level FLOAT NOT NULL,
    temperature FLOAT,
    battery_level FLOAT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id)
);

-- Create plants table
CREATE TABLE IF NOT EXISTS plants (
    plant_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    species VARCHAR(100),
    moisture_min FLOAT,
    moisture_max FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create plant_sensors table (for many-to-many relationship)
CREATE TABLE IF NOT EXISTS plant_sensors (
    plant_id INT NOT NULL,
    sensor_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (plant_id, sensor_id),
    FOREIGN KEY (plant_id) REFERENCES plants(plant_id),
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id)
);

-- Create alerts table
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    type VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (sensor_id) REFERENCES sensors(sensor_id)
);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings (using INSERT IGNORE to avoid duplicate key errors)
INSERT IGNORE INTO settings (name, value, description) VALUES
('reading_interval', '3600', 'Sensor reading interval in seconds'),
('alert_threshold', '20', 'Battery level alert threshold percentage'),
('data_retention_days', '30', 'Number of days to keep sensor readings'),
('email_notifications', 'true', 'Enable email notifications for alerts');

-- Create indexes for better performance (using IF NOT EXISTS to prevent errors)
-- Note: MySQL doesn't support IF NOT EXISTS for indexes directly, so we'll use a different approach
-- We'll check if the index exists before creating it
SET @dbname = 'SoilSensors';
SET @tablename = 'readings';
SET @indexname = 'idx_readings_timestamp';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (timestamp)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexname = 'idx_readings_sensor_id';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (sensor_id)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tablename = 'alerts';
SET @indexname = 'idx_alerts_status';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (status)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexname = 'idx_alerts_sensor_id';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (sensor_id)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tablename = 'sensors';
SET @indexname = 'idx_sensors_name';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (name)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tablename = 'plants';
SET @indexname = 'idx_plants_name';
SET @sql = IF(
    (SELECT COUNT(1) FROM information_schema.statistics 
     WHERE table_schema = @dbname 
     AND table_name = @tablename 
     AND index_name = @indexname) = 0,
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (name)'),
    'SELECT "Index already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 