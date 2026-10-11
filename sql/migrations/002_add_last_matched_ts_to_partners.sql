-- Migration: Add last_matched_ts to bi_partners_data for recency-based scoring
-- Date: 2025-04-15
-- Purpose: Track when each partner was last successfully matched for recency scoring

-- Add last_matched_ts column to track recency of matches
ALTER TABLE `0_bi_partners_data` 
    ADD COLUMN `last_matched_ts` DATETIME NULL DEFAULT NULL AFTER `occurrence_count`;

-- Add index for timestamp queries
ALTER TABLE `0_bi_partners_data` 
    ADD INDEX `idx_last_matched_ts` (`last_matched_ts`);
