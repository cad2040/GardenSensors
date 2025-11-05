-- Migration: Update sensors table to separate last_reading value and timestamp
-- Date: 2024-01-XX
-- Description: Change last_reading from TIMESTAMP to DECIMAL and add last_reading_time as TIMESTAMP

-- First, add the new last_reading_time column (check if it exists first)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'sensors' 
     AND COLUMN_NAME = 'last_reading_time') > 0,
    'SELECT "Column last_reading_time already exists"',
    'ALTER TABLE sensors ADD COLUMN last_reading_time TIMESTAMP NULL AFTER last_reading'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing data: move timestamp values to last_reading_time and set last_reading to NULL
-- (This assumes existing last_reading values are timestamps that should be moved)
UPDATE sensors SET last_reading_time = last_reading, last_reading = NULL WHERE last_reading IS NOT NULL;

-- Now change the last_reading column type from TIMESTAMP to DECIMAL
ALTER TABLE sensors MODIFY COLUMN last_reading DECIMAL(5,2) NULL;
