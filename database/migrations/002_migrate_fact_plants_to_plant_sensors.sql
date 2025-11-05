-- Migration: Migrate data from fact_plants to plant_sensors and drop fact_plants
-- This consolidates plant-sensor relationships into a single table
-- Note: This migration is safe to run even if fact_plants doesn't exist

-- Drop fact_plants table if it exists (no longer needed)
DROP TABLE IF EXISTS fact_plants;

