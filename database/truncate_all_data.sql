-- Truncate All Data Script
-- This script truncates all data tables for a completely fresh start
-- Essential data (admin user, settings) will be re-seeded after truncation
-- WARNING: This will delete ALL data in the database!

-- Disable foreign key checks temporarily to allow truncation
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate all data tables (in order to respect foreign key constraints)
TRUNCATE TABLE readings;
TRUNCATE TABLE plant_sensors;
TRUNCATE TABLE pins;
TRUNCATE TABLE sensors;
TRUNCATE TABLE plants;

-- Plant catalog (optional table on older installs)
SET @pc_exists := (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plant_catalog'
);
SET @pc_sql := IF(@pc_exists > 0, 'TRUNCATE TABLE plant_catalog', 'SELECT 1');
PREPARE pc_stmt FROM @pc_sql;
EXECUTE pc_stmt;
DEALLOCATE PREPARE pc_stmt;

TRUNCATE TABLE password_resets;
TRUNCATE TABLE system_log;
TRUNCATE TABLE rate_limits;
TRUNCATE TABLE settings;
TRUNCATE TABLE users;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Note: After truncation, essential data should be re-seeded:
-- - Admin user (id: 1, username: admin, password: password)
-- - Default settings
-- This is handled by the seed_default_data() function in setup.sh

