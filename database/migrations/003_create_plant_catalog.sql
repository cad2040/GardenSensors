-- Plant catalog: reference growing conditions for presets / dropdowns
-- Populated optionally via database/import_plant_catalog.php (see setup.sh hook)

CREATE TABLE IF NOT EXISTS plant_catalog (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_pid VARCHAR(191) NULL COMMENT 'Upstream id (e.g. Open Plantbook pid)',
    common_name VARCHAR(255) NOT NULL,
    scientific_name VARCHAR(255) NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    min_soil_moisture INT(6) NULL,
    max_soil_moisture INT(6) NULL,
    min_temperature DECIMAL(4,1) NULL,
    max_temperature DECIMAL(4,1) NULL,
    min_soil_ec INT(6) NULL COMMENT 'Fertility proxy (uS/cm), e.g. Plantbook',
    max_soil_ec INT(6) NULL,
    min_env_humidity INT(6) NULL,
    max_env_humidity INT(6) NULL,
    preferred_ph_min DECIMAL(3,1) NULL,
    preferred_ph_max DECIMAL(3,1) NULL,
    water_requirement VARCHAR(50) NULL COMMENT 'Categorical: low|medium|high',
    nutrient_level VARCHAR(50) NULL COMMENT 'Categorical nutrient preference',
    sunlight VARCHAR(50) NULL,
    watering_frequency_hours INT(6) NULL,
    source VARCHAR(50) NOT NULL DEFAULT 'manual' COMMENT 'openplantbook|edible_plant_db|openplantdb|manual',
    source_url VARCHAR(512) NULL,
    metadata_json JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_catalog_external (source, external_pid),
    INDEX idx_catalog_common_name (common_name),
    INDEX idx_catalog_scientific (scientific_name),
    INDEX idx_catalog_category (category),
    INDEX idx_catalog_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link user plants to optional catalog preset (idempotent for MariaDB / MySQL 8+)
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND COLUMN_NAME = 'catalog_id'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE plants ADD COLUMN catalog_id INT(6) UNSIGNED NULL AFTER user_id, ADD INDEX idx_plants_catalog_id (catalog_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'plants'
      AND CONSTRAINT_NAME = 'fk_plant_catalog'
);
SET @sql_fk := IF(
    @fk_exists = 0,
    'ALTER TABLE plants ADD CONSTRAINT fk_plant_catalog FOREIGN KEY (catalog_id) REFERENCES plant_catalog(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;
