-- Garden Sensors Database Deployment Script
-- Version: 1.2
-- Description: Creates database and tables for garden sensor monitoring system

-- Set strict mode for better data integrity
SET SQL_MODE = 'STRICT_ALL_TABLES';

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS garden_sensors
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Switch to the database
USE garden_sensors;

-- Create Users table with improved security
CREATE TABLE IF NOT EXISTS Users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    last_login TIMESTAMP NULL,
    failed_login_attempts INT(1) UNSIGNED DEFAULT 0,
    account_locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Create Sensors table with improved indexing and plot information
CREATE TABLE IF NOT EXISTS Sensors (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor VARCHAR(30) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_reading TIMESTAMP NULL,
    plot_url VARCHAR(255),
    plot_type ENUM('moisture', 'temperature', 'humidity') DEFAULT 'moisture',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sensor_name (sensor),
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_plot_type (plot_type)
) ENGINE=InnoDB;

-- Create Readings table with improved indexing
CREATE TABLE IF NOT EXISTS Readings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    reading DECIMAL(5,2) NOT NULL,
    temperature DECIMAL(4,1),
    humidity DECIMAL(4,1),
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor
        FOREIGN KEY (sensor_id) 
        REFERENCES Sensors(id) 
        ON DELETE CASCADE,
    INDEX idx_sensor_time (sensor_id, inserted),
    INDEX idx_reading_time (inserted)
) ENGINE=InnoDB;

-- Create DimPlants table with improved constraints
CREATE TABLE IF NOT EXISTS DimPlants (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plant VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    minSoilMoisture INT(6) NOT NULL CHECK (minSoilMoisture >= 0 AND minSoilMoisture <= 100),
    maxSoilMoisture INT(6) NOT NULL CHECK (maxSoilMoisture >= 0 AND maxSoilMoisture <= 100),
    wateringFrequency INT(6) NOT NULL CHECK (wateringFrequency > 0) COMMENT 'Watering frequency in hours',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plant_name (plant),
    INDEX idx_species (species)
) ENGINE=InnoDB;

-- Create FactPlants table with improved constraints
CREATE TABLE IF NOT EXISTS FactPlants (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    plant_id INT(6) UNSIGNED NOT NULL,
    lastWatered TIMESTAMP NULL,
    nextWatering TIMESTAMP NULL,
    waterAmount INT(6) NOT NULL CHECK (waterAmount >= 0) COMMENT 'Amount of water in ml',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor2
        FOREIGN KEY (sensor_id) 
        REFERENCES Sensors(id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_plant
        FOREIGN KEY (plant_id) 
        REFERENCES DimPlants(id) 
        ON DELETE CASCADE,
    UNIQUE KEY unique_sensor_plant (sensor_id, plant_id),
    INDEX idx_next_watering (nextWatering)
) ENGINE=InnoDB;

-- Create Pins table with improved constraints and merged DimPins functionality
CREATE TABLE IF NOT EXISTS Pins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    pin INT(6) UNSIGNED NOT NULL,
    pinType ENUM('pump', 'sensor', 'relay') NOT NULL,
    description VARCHAR(255),
    status ENUM('active', 'inactive', 'faulty') DEFAULT 'active',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor4
        FOREIGN KEY (sensor_id) 
        REFERENCES Sensors(id) 
        ON DELETE CASCADE,
    UNIQUE KEY unique_pin (pin),
    INDEX idx_pin_type (pinType),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Create SystemLog table with improved indexing
CREATE TABLE IF NOT EXISTS SystemLog (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eventType ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    source VARCHAR(50),
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (eventType),
    INDEX idx_inserted (inserted)
) ENGINE=InnoDB;

-- Create database user with restricted privileges and secure password
CREATE USER IF NOT EXISTS 'garden_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- Grant specific privileges instead of ALL
GRANT SELECT, INSERT, UPDATE ON garden_sensors.Readings TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.Sensors TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.DimPlants TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.FactPlants TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.Pins TO 'garden_user'@'localhost';
GRANT SELECT, INSERT ON garden_sensors.SystemLog TO 'garden_user'@'localhost';

FLUSH PRIVILEGES;

-- Insert initial data
INSERT INTO Sensors (sensor, description, location, status) VALUES
('MAIN_SENSOR', 'Main garden sensor', 'Garden Center', 'active');

-- Insert initial pins for the main sensor
INSERT INTO Pins (sensor_id, pin, pinType) VALUES
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 2, 'sensor'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 3, 'sensor'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 4, 'sensor'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 5, 'sensor'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 12, 'pump'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 13, 'pump'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 14, 'pump'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 15, 'pump'),
((SELECT id FROM Sensors WHERE sensor = 'MAIN_SENSOR'), 16, 'relay');

-- Insert example plants
INSERT INTO DimPlants (plant, species, minSoilMoisture, maxSoilMoisture, wateringFrequency) VALUES
('Tomato Plant', 'Solanum lycopersicum', 40, 80, 24),
('Basil Plant', 'Ocimum basilicum', 30, 70, 12),
('Succulent', 'Various', 20, 50, 168);  -- Weekly watering

-- Create a view for sensor readings with plant information
CREATE OR REPLACE VIEW SensorReadingsView AS
SELECT 
    s.sensor,
    r.reading as moisture_level,
    r.temperature,
    r.humidity,
    r.inserted as reading_time,
    p.plant,
    p.species,
    fp.lastWatered,
    fp.nextWatering,
    fp.waterAmount
FROM Sensors s
JOIN Readings r ON s.id = r.sensor_id
LEFT JOIN FactPlants fp ON s.id = fp.sensor_id
LEFT JOIN DimPlants p ON fp.plant_id = p.id
WHERE s.status = 'active';

-- Create a view for system health monitoring
CREATE OR REPLACE VIEW SystemHealthView AS
SELECT 
    COUNT(DISTINCT s.id) as total_sensors,
    COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) as active_sensors,
    COUNT(DISTINCT CASE WHEN s.status = 'maintenance' THEN s.id END) as maintenance_sensors,
    COUNT(DISTINCT CASE WHEN s.status = 'inactive' THEN s.id END) as inactive_sensors,
    COUNT(DISTINCT CASE WHEN s.last_reading < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN s.id END) as sensors_needing_attention,
    COUNT(DISTINCT CASE WHEN fp.nextWatering < NOW() THEN fp.id END) as plants_needing_water
FROM Sensors s
LEFT JOIN FactPlants fp ON s.id = fp.sensor_id;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create sensors table
CREATE TABLE IF NOT EXISTS sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    description TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create sensor_readings table
CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE
);

-- Create system_log table
CREATE TABLE IF NOT EXISTS system_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    user_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Insert some sample sensors
INSERT INTO sensors (name, type, location, description) VALUES
('Temperature Sensor 1', 'temperature', 'Greenhouse 1', 'Main temperature sensor in greenhouse 1'),
('Humidity Sensor 1', 'humidity', 'Greenhouse 1', 'Main humidity sensor in greenhouse 1'),
('Soil Moisture 1', 'moisture', 'Greenhouse 1', 'Soil moisture sensor for plant bed 1');

-- Insert some sample readings
INSERT INTO sensor_readings (sensor_id, value, unit) VALUES
(1, 25.5, '°C'),
(2, 65.0, '%'),
(3, 45.0, '%');
