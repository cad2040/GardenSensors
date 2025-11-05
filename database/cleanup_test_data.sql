-- Cleanup Test Data Script
-- Removes test data created during test execution
-- This script should be run after tests complete to ensure a clean production database
-- 
-- NOTE: For a completely fresh deployment, consider using truncate_all_data.sql instead
-- which truncates all tables and re-seeds essential data (faster and more thorough)

-- Delete test users (users with username starting with 'testuser_' or email starting with 'test_')
DELETE FROM users 
WHERE username LIKE 'testuser_%' 
   OR email LIKE 'test_%@example.com'
   OR username LIKE 'Test User%'
   OR email LIKE 'testuser%';

-- Delete test sensors (sensors with name containing 'Test' or 'test')
-- Note: This will NOT delete the seed plot data sensors (Tomato, Basil, Lettuce sensors)
-- as they don't have 'Test' in their names
DELETE FROM sensors 
WHERE name LIKE '%Test%' 
   OR name LIKE '%test%'
   OR name LIKE 'Test Pin Sensor%'
   OR name LIKE 'Test Reading Sensor%'
   OR description LIKE '%Test%'
   OR description LIKE '%test%';

-- Delete test plants (plants with name starting with 'Test Plant' or 'Test Threshold Plant')
DELETE FROM plants 
WHERE name LIKE 'Test Plant%' 
   OR name LIKE 'Test Threshold Plant%'
   OR name LIKE 'Invalid Plant%';

-- Delete test readings (readings associated with deleted sensors will be cascade deleted)
-- But we'll also clean up any orphaned readings
DELETE FROM readings 
WHERE sensor_id NOT IN (SELECT id FROM sensors);

-- Delete test plant_sensors links (will be cleaned up by foreign keys, but ensure clean state)
DELETE FROM plant_sensors 
WHERE plant_id NOT IN (SELECT id FROM plants)
   OR sensor_id NOT IN (SELECT id FROM sensors);

-- Delete test pins (pins associated with deleted sensors will be cascade deleted)
DELETE FROM pins 
WHERE sensor_id NOT IN (SELECT id FROM sensors);

-- Reset auto-increment counters (optional, for clean IDs)
-- Note: This is commented out as it may not be desired in all cases
-- ALTER TABLE users AUTO_INCREMENT = 1;
-- ALTER TABLE sensors AUTO_INCREMENT = 1;
-- ALTER TABLE plants AUTO_INCREMENT = 1;

