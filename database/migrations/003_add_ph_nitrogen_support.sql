-- Migration 003: Add soil pH and nitrogen (fertilizer proxy) support
-- Idempotent: safe after schema.sql already includes these columns, and for upgrades.

-- Always widen plot_type ENUM (safe if values already present)
ALTER TABLE sensors
    MODIFY COLUMN plot_type ENUM('moisture', 'temperature', 'humidity', 'ph', 'nitrogen') DEFAULT 'moisture';

-- Add plants.min_ph if missing
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND COLUMN_NAME = 'min_ph'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN min_ph DECIMAL(3,1) DEFAULT 6.0 AFTER max_temperature',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add plants.max_ph if missing
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND COLUMN_NAME = 'max_ph'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN max_ph DECIMAL(3,1) DEFAULT 7.0 AFTER min_ph',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add plants.min_nitrogen if missing
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND COLUMN_NAME = 'min_nitrogen'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN min_nitrogen DECIMAL(8,2) DEFAULT 10.00 COMMENT ''Reference soil nitrate-N/probe estimate lower bound (mg/kg)'' AFTER max_ph',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add plants.max_nitrogen if missing
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND COLUMN_NAME = 'max_nitrogen'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN max_nitrogen DECIMAL(8,2) DEFAULT 20.00 COMMENT ''Reference soil nitrate-N/probe estimate upper bound (mg/kg)'' AFTER min_nitrogen',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed horticultural defaults for known sample plants (idempotent updates)
UPDATE plants SET min_ph = 6.0, max_ph = 6.8, min_nitrogen = 10.00, max_nitrogen = 20.00
WHERE name = 'Tomato Plant';

UPDATE plants SET min_ph = 6.0, max_ph = 7.5, min_nitrogen = 10.00, max_nitrogen = 20.00
WHERE name = 'Basil Plant';

UPDATE plants SET min_ph = 6.0, max_ph = 7.0, min_nitrogen = 10.00, max_nitrogen = 20.00
WHERE name = 'Lettuce Plant';

UPDATE plants SET min_ph = 6.0, max_ph = 7.5, min_nitrogen = 10.00, max_nitrogen = 20.00
WHERE name = 'Succulent';
