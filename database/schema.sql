-- Garden Sensors Database Schema
-- Version: 1.3
-- Description: Consolidated schema for garden sensor monitoring system

-- Set strict mode for better data integrity
SET SQL_MODE = 'STRICT_ALL_TABLES';

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS garden_sensors
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Switch to the database
USE garden_sensors;

-- Create Users table
CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    failed_login_attempts INT(1) UNSIGNED DEFAULT 0,
    account_locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Create Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- Create Sensors table
CREATE TABLE IF NOT EXISTS sensors (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    description TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    last_reading DECIMAL(5,2) NULL,
    last_reading_time TIMESTAMP NULL,
    min_threshold DECIMAL(5,2) DEFAULT 20.00,
    max_threshold DECIMAL(5,2) DEFAULT 80.00,
    unit VARCHAR(20) DEFAULT 'percentage',
    plot_url VARCHAR(255),
    plot_type ENUM('moisture', 'temperature', 'humidity') DEFAULT 'moisture',
    user_id INT(6) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor_user
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_plot_type (plot_type)
) ENGINE=InnoDB;

-- Create Readings table
CREATE TABLE IF NOT EXISTS readings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    temperature DECIMAL(4,1),
    humidity DECIMAL(4,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor
        FOREIGN KEY (sensor_id) 
        REFERENCES sensors(id) 
        ON DELETE CASCADE,
    INDEX idx_sensor_time (sensor_id, created_at),
    INDEX idx_reading_time (created_at)
) ENGINE=InnoDB;

-- Create Plants table
CREATE TABLE IF NOT EXISTS plants (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    description TEXT,
    planting_date DATE,
    harvest_date DATE,
    min_soil_moisture INT(6) NOT NULL CHECK (min_soil_moisture >= 0 AND min_soil_moisture <= 100),
    max_soil_moisture INT(6) NOT NULL CHECK (max_soil_moisture >= 0 AND max_soil_moisture <= 100),
    watering_frequency INT(6) NOT NULL CHECK (watering_frequency > 0) COMMENT 'Watering frequency in hours',
    location VARCHAR(255),
    min_temperature DECIMAL(4,1),
    max_temperature DECIMAL(4,1),
    status ENUM('active', 'inactive', 'harvested') DEFAULT 'active',
    user_id INT(6) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_plant_user
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL,
    UNIQUE KEY unique_plant_name (name),
    INDEX idx_species (species),
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Create Plant_Sensors table
CREATE TABLE IF NOT EXISTS plant_sensors (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    plant_id INT(6) UNSIGNED NOT NULL,
    last_watered TIMESTAMP NULL,
    next_watering TIMESTAMP NULL,
    water_amount INT(6) NOT NULL CHECK (water_amount >= 0) COMMENT 'Amount of water in ml',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor2
        FOREIGN KEY (sensor_id) 
        REFERENCES sensors(id) 
        ON DELETE CASCADE,
    CONSTRAINT fk_plant
        FOREIGN KEY (plant_id) 
        REFERENCES plants(id) 
        ON DELETE CASCADE,
    UNIQUE KEY unique_sensor_plant (sensor_id, plant_id),
    INDEX idx_next_watering (next_watering)
) ENGINE=InnoDB;

-- fact_plants table has been removed - use plant_sensors instead

-- Create Pins table
CREATE TABLE IF NOT EXISTS pins (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT(6) UNSIGNED NOT NULL,
    pin_number INT(6) UNSIGNED NOT NULL,
    pin VARCHAR(10) NOT NULL,
    pinType ENUM('pump', 'sensor', 'relay') NOT NULL,
    pin_type ENUM('pump', 'sensor', 'relay') NOT NULL,
    description VARCHAR(255),
    status ENUM('active', 'inactive', 'faulty') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sensor4
        FOREIGN KEY (sensor_id) 
        REFERENCES sensors(id) 
        ON DELETE CASCADE,
    UNIQUE KEY unique_pin (pin_number),
    INDEX idx_pin_type (pin_type),
    INDEX idx_status (status)
) ENGINE=InnoDB;



-- Create Rate Limits table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rate_limit_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_user_endpoint_time (user_id, endpoint, timestamp),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- Create Password Resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- Create System Log table
CREATE TABLE IF NOT EXISTS system_log (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    source VARCHAR(50),
    user_id INT(6) UNSIGNED,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Create database user with restricted privileges
CREATE USER IF NOT EXISTS 'garden_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- Grant specific privileges
GRANT SELECT, INSERT, UPDATE ON garden_sensors.readings TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.sensors TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.plants TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.plant_sensors TO 'garden_user'@'localhost';
GRANT SELECT ON garden_sensors.pins TO 'garden_user'@'localhost';
GRANT SELECT, INSERT ON garden_sensors.system_log TO 'garden_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON garden_sensors.settings TO 'garden_user'@'localhost';

FLUSH PRIVILEGES;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Insert default settings
INSERT INTO settings (name, value) VALUES
('update_interval', '300'),
('timezone', 'UTC'),
('plot_type', 'moisture');

-- Insert sample sensors
INSERT INTO sensors (name, type, location, description) VALUES
('Temperature Sensor 1', 'temperature', 'Greenhouse 1', 'Main temperature sensor in greenhouse 1'),
('Humidity Sensor 1', 'humidity', 'Greenhouse 1', 'Main humidity sensor in greenhouse 1'),
('Soil Moisture 1', 'moisture', 'Greenhouse 1', 'Soil moisture sensor for plant bed 1');

-- Insert sample plants
INSERT INTO plants (name, species, min_soil_moisture, max_soil_moisture, watering_frequency) VALUES
('Tomato Plant', 'Solanum lycopersicum', 40, 80, 24),
('Basil Plant', 'Ocimum basilicum', 30, 70, 12),
('Succulent', 'Various', 20, 50, 168);  -- Weekly watering 