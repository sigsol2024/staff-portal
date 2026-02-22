-- =============================================================================
-- Single migration: update existing database to current schema
-- =============================================================================
-- Run in phpMyAdmin: open your database > SQL tab > paste this file > Go.
-- Safe to run multiple times: only adds columns/tables that don't exist.
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS add_staff_column_if_not_exists//
CREATE PROCEDURE add_staff_column_if_not_exists(
  IN p_column VARCHAR(64),
  IN p_definition TEXT
)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = p_column) = 0 THEN
    SET @sql = CONCAT('ALTER TABLE `staff` ADD COLUMN `', p_column, '` ', p_definition);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//

DELIMITER ;

-- Staff table: add each column only if it does not exist
CALL add_staff_column_if_not_exists('confirmation_date', 'date DEFAULT NULL AFTER `date_joined`');
CALL add_staff_column_if_not_exists('marital_status', 'varchar(50) DEFAULT NULL AFTER `address`');
CALL add_staff_column_if_not_exists('employee_id', 'varchar(100) DEFAULT NULL AFTER `position`');
CALL add_staff_column_if_not_exists('department', 'varchar(255) DEFAULT NULL AFTER `employee_id`');
CALL add_staff_column_if_not_exists('employment_type', 'varchar(50) DEFAULT NULL COMMENT ''Full-time/Part-time/Contract'' AFTER `department`');
CALL add_staff_column_if_not_exists('reporting_manager', 'varchar(255) DEFAULT NULL AFTER `employment_type`');
CALL add_staff_column_if_not_exists('work_location', 'varchar(255) DEFAULT NULL AFTER `reporting_manager`');
CALL add_staff_column_if_not_exists('basic_salary', 'decimal(12,2) DEFAULT NULL AFTER `work_location`');
CALL add_staff_column_if_not_exists('housing_allowance', 'decimal(12,2) DEFAULT NULL AFTER `basic_salary`');
CALL add_staff_column_if_not_exists('transport_allowance', 'decimal(12,2) DEFAULT NULL AFTER `housing_allowance`');
CALL add_staff_column_if_not_exists('other_allowances', 'text DEFAULT NULL AFTER `transport_allowance`');
CALL add_staff_column_if_not_exists('gross_monthly_salary', 'decimal(12,2) DEFAULT NULL AFTER `other_allowances`');
CALL add_staff_column_if_not_exists('overtime_rate', 'varchar(100) DEFAULT NULL AFTER `gross_monthly_salary`');
CALL add_staff_column_if_not_exists('bonus_commission_structure', 'text DEFAULT NULL AFTER `overtime_rate`');
CALL add_staff_column_if_not_exists('bank_name', 'varchar(255) DEFAULT NULL AFTER `bonus_commission_structure`');
CALL add_staff_column_if_not_exists('account_name', 'varchar(255) DEFAULT NULL AFTER `bank_name`');
CALL add_staff_column_if_not_exists('account_number', 'varchar(100) DEFAULT NULL AFTER `account_name`');
CALL add_staff_column_if_not_exists('bvn', 'varchar(50) DEFAULT NULL AFTER `account_number`');
CALL add_staff_column_if_not_exists('tax_identification_number', 'varchar(100) DEFAULT NULL COMMENT ''TIN'' AFTER `bvn`');
CALL add_staff_column_if_not_exists('pension_fund_administrator', 'varchar(255) DEFAULT NULL AFTER `tax_identification_number`');
CALL add_staff_column_if_not_exists('pension_pin', 'varchar(100) DEFAULT NULL AFTER `pension_fund_administrator`');
CALL add_staff_column_if_not_exists('nhf_number', 'varchar(100) DEFAULT NULL AFTER `pension_pin`');
CALL add_staff_column_if_not_exists('nhis_hmo_provider', 'varchar(255) DEFAULT NULL AFTER `nhf_number`');
CALL add_staff_column_if_not_exists('employee_contribution_percentages', 'text DEFAULT NULL AFTER `nhis_hmo_provider`');
CALL add_staff_column_if_not_exists('new_hire', 'tinyint(1) DEFAULT NULL COMMENT ''1=Yes, 0=No'' AFTER `employee_contribution_percentages`');
CALL add_staff_column_if_not_exists('exit_termination_date', 'date DEFAULT NULL AFTER `new_hire`');
CALL add_staff_column_if_not_exists('salary_adjustment_notes', 'text DEFAULT NULL AFTER `exit_termination_date`');
CALL add_staff_column_if_not_exists('promotion_role_change', 'text DEFAULT NULL AFTER `salary_adjustment_notes`');
CALL add_staff_column_if_not_exists('bank_detail_update', 'text DEFAULT NULL AFTER `promotion_role_change`');
CALL add_staff_column_if_not_exists('declaration_accepted', 'tinyint(1) DEFAULT NULL AFTER `bank_detail_update`');
CALL add_staff_column_if_not_exists('email_verified', 'tinyint(1) NOT NULL DEFAULT 1 AFTER `declaration_accepted`');

DROP PROCEDURE IF EXISTS add_staff_column_if_not_exists;

-- Email verification and login OTP codes
CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'registration',
  `user_type` varchar(10) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_type` (`email`,`type`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
