-- Create sensor_readings view
-- This view maps the readings table to the expected sensor_readings structure
-- used by the web application

CREATE OR REPLACE VIEW sensor_readings AS
SELECT 
    r.id,
    r.sensor_id,
    s.type AS sensor_type,
    r.value AS reading_value,
    r.created_at AS reading_timestamp,
    r.temperature,
    r.humidity,
    r.unit
FROM readings r
JOIN sensors s ON r.sensor_id = s.id;

