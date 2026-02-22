-- Migration 001: Add address, gender, phone_number to staff table
-- Copy and paste this into phpMyAdmin > SQL tab and run once.
-- If you get "Duplicate column name" error, that column already exists (skip or remove that line).

ALTER TABLE `staff` ADD COLUMN `phone_number` VARCHAR(50) DEFAULT NULL AFTER `biography`;
ALTER TABLE `staff` ADD COLUMN `gender` VARCHAR(20) DEFAULT NULL AFTER `phone_number`;
ALTER TABLE `staff` ADD COLUMN `address` TEXT DEFAULT NULL AFTER `gender`;
