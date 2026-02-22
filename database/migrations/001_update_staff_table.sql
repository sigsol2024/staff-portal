-- =============================================================================
-- Migration: Update staff table with all current columns
-- =============================================================================
-- Use this to update an EXISTING database online (e.g. already has staff table).
-- Run in phpMyAdmin: open your database > SQL tab > paste this file > Go.
--
-- If you see "Duplicate column name" for a line, that column already exists.
-- Comment out that line and run again. (phone_number, gender, address are
-- omitted here since your DB already has them.)
-- =============================================================================

ALTER TABLE `staff` ADD COLUMN `confirmation_date` date DEFAULT NULL AFTER `date_joined`;
ALTER TABLE `staff` ADD COLUMN `marital_status` varchar(50) DEFAULT NULL AFTER `address`;
ALTER TABLE `staff` ADD COLUMN `employee_id` varchar(100) DEFAULT NULL AFTER `position`;
ALTER TABLE `staff` ADD COLUMN `department` varchar(255) DEFAULT NULL AFTER `employee_id`;
ALTER TABLE `staff` ADD COLUMN `employment_type` varchar(50) DEFAULT NULL COMMENT 'Full-time/Part-time/Contract' AFTER `department`;
ALTER TABLE `staff` ADD COLUMN `reporting_manager` varchar(255) DEFAULT NULL AFTER `employment_type`;
ALTER TABLE `staff` ADD COLUMN `work_location` varchar(255) DEFAULT NULL AFTER `reporting_manager`;
ALTER TABLE `staff` ADD COLUMN `basic_salary` decimal(12,2) DEFAULT NULL AFTER `work_location`;
ALTER TABLE `staff` ADD COLUMN `housing_allowance` decimal(12,2) DEFAULT NULL AFTER `basic_salary`;
ALTER TABLE `staff` ADD COLUMN `transport_allowance` decimal(12,2) DEFAULT NULL AFTER `housing_allowance`;
ALTER TABLE `staff` ADD COLUMN `other_allowances` text DEFAULT NULL AFTER `transport_allowance`;
ALTER TABLE `staff` ADD COLUMN `gross_monthly_salary` decimal(12,2) DEFAULT NULL AFTER `other_allowances`;
ALTER TABLE `staff` ADD COLUMN `overtime_rate` varchar(100) DEFAULT NULL AFTER `gross_monthly_salary`;
ALTER TABLE `staff` ADD COLUMN `bonus_commission_structure` text DEFAULT NULL AFTER `overtime_rate`;
ALTER TABLE `staff` ADD COLUMN `bank_name` varchar(255) DEFAULT NULL AFTER `bonus_commission_structure`;
ALTER TABLE `staff` ADD COLUMN `account_name` varchar(255) DEFAULT NULL AFTER `bank_name`;
ALTER TABLE `staff` ADD COLUMN `account_number` varchar(100) DEFAULT NULL AFTER `account_name`;
ALTER TABLE `staff` ADD COLUMN `bvn` varchar(50) DEFAULT NULL AFTER `account_number`;
ALTER TABLE `staff` ADD COLUMN `tax_identification_number` varchar(100) DEFAULT NULL COMMENT 'TIN' AFTER `bvn`;
ALTER TABLE `staff` ADD COLUMN `pension_fund_administrator` varchar(255) DEFAULT NULL AFTER `tax_identification_number`;
ALTER TABLE `staff` ADD COLUMN `pension_pin` varchar(100) DEFAULT NULL AFTER `pension_fund_administrator`;
ALTER TABLE `staff` ADD COLUMN `nhf_number` varchar(100) DEFAULT NULL AFTER `pension_pin`;
ALTER TABLE `staff` ADD COLUMN `nhis_hmo_provider` varchar(255) DEFAULT NULL AFTER `nhf_number`;
ALTER TABLE `staff` ADD COLUMN `employee_contribution_percentages` text DEFAULT NULL AFTER `nhis_hmo_provider`;
ALTER TABLE `staff` ADD COLUMN `new_hire` tinyint(1) DEFAULT NULL COMMENT '1=Yes, 0=No' AFTER `employee_contribution_percentages`;
ALTER TABLE `staff` ADD COLUMN `exit_termination_date` date DEFAULT NULL AFTER `new_hire`;
ALTER TABLE `staff` ADD COLUMN `salary_adjustment_notes` text DEFAULT NULL AFTER `exit_termination_date`;
ALTER TABLE `staff` ADD COLUMN `promotion_role_change` text DEFAULT NULL AFTER `salary_adjustment_notes`;
ALTER TABLE `staff` ADD COLUMN `bank_detail_update` text DEFAULT NULL AFTER `promotion_role_change`;
ALTER TABLE `staff` ADD COLUMN `declaration_accepted` tinyint(1) DEFAULT NULL AFTER `bank_detail_update`;
