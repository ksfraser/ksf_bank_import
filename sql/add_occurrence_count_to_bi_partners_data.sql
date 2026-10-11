-- Migration: Add occurrence_count to bi_partners_data for keyword scoring
-- Date: 2025-10-20
-- Purpose: Enable frequency-based pattern matching for transaction keywords

-- Add occurrence_count column to track how often a keyword matches a partner
ALTER TABLE `0_bi_partners_data` 
    ADD COLUMN `occurrence_count` INTEGER DEFAULT 1 AFTER `data`;

-- Update existing records to have count of 1
UPDATE `0_bi_partners_data` SET `occurrence_count` = 1 WHERE `occurrence_count` IS NULL;

-- Add index for faster keyword lookups
ALTER TABLE `0_bi_partners_data` 
    ADD INDEX `idx_partner_type_data` (`partner_type`, `data`);

-- Note: The unique constraint on (partner_id, partner_detail_id, partner_type) 
-- will need to be dropped and recreated to include 'data' for keyword-based scoring
ALTER TABLE `0_bi_partners_data` DROP INDEX `idx`;

-- New composite unique key: same partner can have multiple keywords
-- but each keyword is unique per partner/detail/type combination
ALTER TABLE `0_bi_partners_data` 
    ADD CONSTRAINT `idx_partner_keyword` UNIQUE(`partner_id`, `partner_detail_id`, `partner_type`, `data`);

-- Add index on occurrence_count for scoring queries
ALTER TABLE `0_bi_partners_data` 
    ADD INDEX `idx_occurrence_count` (`occurrence_count` DESC);
