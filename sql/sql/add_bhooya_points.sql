-- Migration: Add bhooya_points column to match_results table
-- Date: 2025-11-05
-- Description: Adds a new bhooya_points column to store additional points for winners/special achievements

-- Add bhooya_points column if it doesn't exist
ALTER TABLE match_results 
ADD COLUMN IF NOT EXISTS bhooya_points DECIMAL(10,2) DEFAULT 0 
AFTER kill_points;

-- Update the total_points computed column to include bhooya_points
-- Note: MySQL doesn't support ALTER on generated columns directly
-- So we need to drop and recreate it

-- First, check if we need to update the generated column
-- Drop the generated column if it exists
ALTER TABLE match_results 
DROP COLUMN IF EXISTS total_points;

-- Recreate the total_points column with bhooya_points included
ALTER TABLE match_results 
ADD COLUMN total_points DECIMAL(10,2) 
GENERATED ALWAYS AS (placement_points + kill_points + bhooya_points + bonus_points) STORED;

-- Verify the changes
DESCRIBE match_results;

SELECT 'Bhooya points column added successfully!' AS Status;
