-- Migration: Fix production tables
-- Version: 1.4
-- Description: Adds missing columns and fixes default values

-- Add status column to users table if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';

-- Modify name columns to have default values
ALTER TABLE sensors 
MODIFY name VARCHAR(100) NOT NULL DEFAULT '';

ALTER TABLE plants 
MODIFY name VARCHAR(255) NOT NULL DEFAULT ''; 