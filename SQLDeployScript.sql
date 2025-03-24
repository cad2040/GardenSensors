-- Garden Sensors Database Deployment Script
-- Version: 1.0
-- Description: Creates database and tables for garden sensor monitoring system

-- Set strict mode for better data integrity
SET SQL_MODE = 'STRICT_ALL_TABLES';

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS SoilSensors
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Switch to the database
USE SoilSensors;

-- Create Sensors table
CREATE TABLE IF NOT EXISTS Sensors (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor VARCHAR(30) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sensor_name (sensor)
) ENGINE=InnoDB;

-- Create Readings table
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
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create DimPlants table
CREATE TABLE IF NOT EXISTS DimPlants (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plant VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    minSoilMoisture INT(6) NOT NULL,
    maxSoilMoisture INT(6) NOT NULL,
    wateringFrequency INT(6) COMMENT 'Watering frequency in hours',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_plant_name (plant)
) ENGINE=InnoDB;

-- Create FactPlants table
CREATE TABLE IF NOT EXISTS FactPlants (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    plant_id INT(6) UNSIGNED NOT NULL,
    lastWatered TIMESTAMP NULL,
    nextWatering TIMESTAMP NULL,
    waterAmount INT(6) COMMENT 'Amount of water in ml',
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
    UNIQUE KEY unique_sensor_plant (sensor_id, plant_id)
) ENGINE=InnoDB;

-- Create Plots table
CREATE TABLE IF NOT EXISTS Plots (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    sensor VARCHAR(255) NOT NULL,
    URL VARCHAR(255) NOT NULL,
    plotType ENUM('moisture', 'temperature', 'humidity') DEFAULT 'moisture',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor3
        FOREIGN KEY (sensor_id) 
        REFERENCES Sensors(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create Pins table
CREATE TABLE IF NOT EXISTS Pins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    pin INT(6) UNSIGNED NOT NULL,
    pinType ENUM('pump', 'sensor', 'relay') NOT NULL,
    status ENUM('active', 'inactive', 'faulty') DEFAULT 'active',
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor4
        FOREIGN KEY (sensor_id) 
        REFERENCES Sensors(id) 
        ON DELETE CASCADE,
    UNIQUE KEY unique_pin (pin)
) ENGINE=InnoDB;

-- Create DimPins table
CREATE TABLE IF NOT EXISTS DimPins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pin INT(6) UNSIGNED NOT NULL,
    description VARCHAR(255),
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pin (pin)
) ENGINE=InnoDB;

-- Create SystemLog table for monitoring
CREATE TABLE IF NOT EXISTS SystemLog (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    eventType ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    source VARCHAR(50),
    inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create indexes for better performance
CREATE INDEX idx_readings_sensor_id ON Readings(sensor_id);
CREATE INDEX idx_readings_inserted ON Readings(inserted);
CREATE INDEX idx_factplants_sensor_id ON FactPlants(sensor_id);
CREATE INDEX idx_factplants_plant_id ON FactPlants(plant_id);
CREATE INDEX idx_plots_sensor_id ON Plots(sensor_id);
CREATE INDEX idx_pins_sensor_id ON Pins(sensor_id);

-- Create database user with restricted privileges
CREATE USER IF NOT EXISTS 'garden_user'@'%' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- Grant specific privileges instead of ALL
GRANT SELECT, INSERT, UPDATE ON SoilSensors.Readings TO 'garden_user'@'%';
GRANT SELECT ON SoilSensors.Sensors TO 'garden_user'@'%';
GRANT SELECT ON SoilSensors.DimPlants TO 'garden_user'@'%';
GRANT SELECT ON SoilSensors.FactPlants TO 'garden_user'@'%';
GRANT SELECT ON SoilSensors.Pins TO 'garden_user'@'%';
GRANT SELECT ON SoilSensors.DimPins TO 'garden_user'@'%';
GRANT SELECT, INSERT, UPDATE ON SoilSensors.Plots TO 'garden_user'@'%';
GRANT SELECT, INSERT ON SoilSensors.SystemLog TO 'garden_user'@'%';

FLUSH PRIVILEGES;

-- Insert some initial data
INSERT INTO DimPins (pin, description) VALUES
(2, 'GPIO2'),
(3, 'GPIO3'),
(4, 'GPIO4'),
(5, 'GPIO5'),
(12, 'GPIO12'),
(13, 'GPIO13'),
(14, 'GPIO14'),
(15, 'GPIO15'),
(16, 'GPIO16');

-- Insert some example plants
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
