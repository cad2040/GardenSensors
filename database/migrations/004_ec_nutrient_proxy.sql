-- Migration 004: Replace nitrogen (mg/kg) with EC nutrient proxy (mS/cm)

ALTER TABLE sensors
    MODIFY COLUMN plot_type ENUM('moisture', 'temperature', 'humidity', 'ph', 'nitrogen', 'ec') DEFAULT 'moisture';

UPDATE sensors
SET type = 'ec', plot_type = 'ec', unit = 'mS/cm'
WHERE type = 'nitrogen' OR plot_type = 'nitrogen';

ALTER TABLE sensors
    MODIFY COLUMN plot_type ENUM('moisture', 'temperature', 'humidity', 'ph', 'ec') DEFAULT 'moisture';

-- ec_underfed
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'ec_underfed'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN ec_underfed DECIMAL(6,2) DEFAULT 0.80 COMMENT ''Available EC below = underfed (mS/cm)'' AFTER max_ph',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'ec_balanced_min'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN ec_balanced_min DECIMAL(6,2) DEFAULT 1.00 AFTER ec_underfed',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'ec_balanced_max'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN ec_balanced_max DECIMAL(6,2) DEFAULT 1.60 AFTER ec_balanced_min',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'ec_overfed'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN ec_overfed DECIMAL(6,2) DEFAULT 2.00 COMMENT ''Available EC above = overfed risk (mS/cm)'' AFTER ec_balanced_max',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE plants SET
    ec_underfed = 0.80,
    ec_balanced_min = 1.00,
    ec_balanced_max = 1.60,
    ec_overfed = 2.00
WHERE ec_underfed IS NULL OR ec_balanced_min IS NULL;

-- Drop legacy nitrogen columns when present
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'min_nitrogen'
);
SET @sql := IF(
    @col_exists > 0,
    'ALTER TABLE plants DROP COLUMN min_nitrogen',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants' AND COLUMN_NAME = 'max_nitrogen'
);
SET @sql := IF(
    @col_exists > 0,
    'ALTER TABLE plants DROP COLUMN max_nitrogen',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
