-- Migration: Fix test tables and add missing columns
-- Version: 1.4
-- Description: Adds test table and fixes missing columns

-- Create test table for DatabaseTest
CREATE TABLE IF NOT EXISTS test (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL
);

-- Add status column to users table if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';

-- Modify name columns to have default values
ALTER TABLE sensors 
MODIFY name VARCHAR(100) NOT NULL DEFAULT '';

ALTER TABLE plants 
MODIFY name VARCHAR(255) NOT NULL DEFAULT '';

-- Create test_models table for BaseModelTest
CREATE TABLE IF NOT EXISTS test_models (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB; 