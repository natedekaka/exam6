-- Migration: Increase default max_violations from 3 to 10
-- Run this to reduce false-positive auto-submit caused by phone screen-off/idle

-- Change the default value for new exams
ALTER TABLE ujian MODIFY COLUMN max_violations INT DEFAULT 10;

-- Update existing exams that still use the old default (3) to the new default (10)
-- This does NOT affect exams where admin has manually set a different value
UPDATE ujian SET max_violations = 10 WHERE max_violations = 3;
