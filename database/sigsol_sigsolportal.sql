-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 22, 2026 at 05:39 AM
-- Server version: 10.6.25-MariaDB
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigsol_sigsolportal`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `staff_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `staff_id`, `created_at`) VALUES
(1, 2, 'add_staff', 1, '2026-02-22 05:05:55');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', '2026-02-22 04:45:30'),
(2, 'portal@signature-solutions.com', '$2y$10$7RaD61kFsm.1KR.I89Igy.1/gD5mje8W32EmgTt3TpOLtJNP5Iy8a', '2026-02-22 04:56:02');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
-- Single schema: includes all columns (no separate migrations)
--

CREATE TABLE `staff` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date_joined` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `employee_id` varchar(100) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL COMMENT 'Full-time/Part-time/Contract',
  `reporting_manager` varchar(255) DEFAULT NULL,
  `work_location` varchar(255) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT NULL,
  `housing_allowance` decimal(12,2) DEFAULT NULL,
  `transport_allowance` decimal(12,2) DEFAULT NULL,
  `other_allowances` text DEFAULT NULL,
  `gross_monthly_salary` decimal(12,2) DEFAULT NULL,
  `overtime_rate` varchar(100) DEFAULT NULL,
  `bonus_commission_structure` text DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `bvn` varchar(50) DEFAULT NULL,
  `tax_identification_number` varchar(100) DEFAULT NULL COMMENT 'TIN',
  `pension_fund_administrator` varchar(255) DEFAULT NULL,
  `pension_pin` varchar(100) DEFAULT NULL,
  `nhf_number` varchar(100) DEFAULT NULL,
  `nhis_hmo_provider` varchar(255) DEFAULT NULL,
  `employee_contribution_percentages` text DEFAULT NULL,
  `new_hire` tinyint(1) DEFAULT NULL COMMENT '1=Yes, 0=No',
  `exit_termination_date` date DEFAULT NULL,
  `salary_adjustment_notes` text DEFAULT NULL,
  `promotion_role_change` text DEFAULT NULL,
  `bank_detail_update` text DEFAULT NULL,
  `declaration_accepted` tinyint(1) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `verification_codes`
--
CREATE TABLE `verification_codes` (
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

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `email`, `password`, `full_name`, `date_of_birth`, `date_joined`, `position`, `biography`, `phone_number`, `gender`, `address`, `profile_image`, `status`, `failed_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, 'dm@signature-solutions.com', '$2y$10$8rM57AlJ13MjAIvqkAXo8uzQrF2qTwygh3Ex7IG3xstdtxBknTXVm', 'james samaila', '2004-05-28', '2026-01-04', 'we developer', 'very hard working', NULL, NULL, NULL, NULL, 'active', 0, NULL, '2026-02-22 05:05:55', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
