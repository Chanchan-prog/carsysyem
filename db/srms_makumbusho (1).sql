-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 01:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `srms_makumbusho`
--

-- --------------------------------------------------------

--
-- Table structure for table `appeals`
--

CREATE TABLE `appeals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('open','resolved') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appeals`
--

INSERT INTO `appeals` (`id`, `user_id`, `reason`, `status`, `created_at`) VALUES
(1, 2, 'sorry i cant', 'resolved', '2025-09-04 15:46:14');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `model` varchar(120) NOT NULL,
  `plate_no` varchar(20) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `model`, `plate_no`, `price`, `is_active`, `archived`) VALUES
(4, 'asdasd', 'ASD-5930', 123123.00, 1, 0),
(5, 'dasd', 'DAS-5379', 123123.00, 1, 0),
(10, 'asdasdasd', 'ASD-5729', 12300.00, 1, 0),
(41, 'Toyota Vios', 'VIO-0001', 750000.00, 1, 0),
(42, 'Toyota Wigo', 'WIG-0002', 570000.00, 1, 0),
(43, 'Toyota Innova', 'INN-0003', 1300000.00, 1, 0),
(44, 'Toyota Fortuner', 'FOR-0004', 2000000.00, 1, 0),
(45, 'Toyota Hilux', 'HIL-0005', 1200000.00, 1, 0),
(46, 'Honda City', 'CIT-0006', 950000.00, 1, 0),
(47, 'Honda Civic', 'CIV-0007', 1600000.00, 1, 0),
(48, 'Honda BR-V', 'BRV-0008', 1300000.00, 1, 0),
(49, 'Mitsubishi Mirage', 'MIR-0009', 700000.00, 1, 0),
(50, 'Mitsubishi Xpander', 'XPA-0010', 1200000.00, 1, 0),
(51, 'Mitsubishi Montero Sport', 'MON-0011', 1900000.00, 1, 0),
(52, 'Nissan Almera', 'ALM-0012', 900000.00, 1, 0),
(53, 'Nissan Terra', 'TER-0013', 1900000.00, 1, 0),
(54, 'Nissan Navara', 'NAV-0014', 1300000.00, 1, 0),
(55, 'Suzuki Ertiga', 'ERT-0015', 950000.00, 1, 0),
(56, 'Suzuki S-Presso', 'SPR-0016', 600000.00, 1, 0),
(57, 'Suzuki Jimny', 'JIM-0017', 1300000.00, 1, 0),
(58, 'Hyundai Accent', 'ACC-0018', 900000.00, 1, 0),
(59, 'Hyundai Tucson', 'TUC-0019', 1800000.00, 1, 0),
(61, 'Ford Everest', 'EVE-0021', 2200000.00, 1, 0),
(62, 'Ford Territory', 'TER-0022', 1600000.00, 1, 0),
(63, 'Mazda 2', 'MZ2-0023', 1000000.00, 1, 0),
(64, 'Mazda 3', 'MZ3-0024', 1500000.00, 1, 0),
(65, 'Mazda CX-5', 'CX5-0025', 2000000.00, 1, 0),
(66, 'Kia Stonic', 'STO-0026', 900000.00, 1, 0),
(67, 'Kia Soluto', 'SOL-0027', 720000.00, 1, 0),
(68, 'Isuzu mu-X', 'MUX-0028', 2100000.00, 1, 0),
(69, 'Isuzu D-Max', 'DMX-0029', 1300000.00, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `car_insurance`
--

CREATE TABLE `car_insurance` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `insurance_company` varchar(100) NOT NULL,
  `policy_number` varchar(50) NOT NULL,
  `coverage_type` enum('comprehensive','third_party','collision') NOT NULL,
  `premium_amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `document_type` enum('identity','income','employment','vehicle','insurance','other') NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emis`
--

CREATE TABLE `emis` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `installment_no` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `principal_component` decimal(12,2) NOT NULL,
  `interest_component` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('due','paid','late') DEFAULT 'due',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emis`
--

INSERT INTO `emis` (`id`, `loan_id`, `installment_no`, `due_date`, `principal_component`, `interest_component`, `amount`, `status`, `paid_at`) VALUES
(1, 1, 1, '2025-10-04', 553.42, 192.69, 746.11, 'paid', '2025-09-04 22:50:50'),
(2, 1, 2, '2025-11-04', 558.03, 188.08, 746.11, 'paid', '2025-09-04 22:50:51'),
(3, 1, 3, '2025-12-04', 562.68, 183.43, 746.11, 'paid', '2025-09-04 22:50:52'),
(4, 1, 4, '2026-01-04', 567.37, 178.74, 746.11, 'paid', '2025-09-04 22:50:52'),
(5, 1, 5, '2026-02-04', 572.10, 174.01, 746.11, 'paid', '2025-09-04 22:50:52'),
(6, 1, 6, '2026-03-04', 576.86, 169.25, 746.11, 'paid', '2025-09-04 23:12:49'),
(7, 1, 7, '2026-04-04', 581.67, 164.44, 746.11, 'paid', '2025-09-04 23:13:03'),
(8, 1, 8, '2026-05-04', 586.52, 159.59, 746.11, 'paid', '2025-09-04 23:16:07'),
(9, 1, 9, '2026-06-04', 591.41, 154.70, 746.11, 'due', NULL),
(10, 1, 10, '2026-07-04', 596.34, 149.77, 746.11, 'due', NULL),
(11, 1, 11, '2026-08-04', 601.30, 144.81, 746.11, 'due', NULL),
(12, 1, 12, '2026-09-04', 606.32, 139.79, 746.11, 'due', NULL),
(13, 1, 13, '2026-10-04', 611.37, 134.74, 746.11, 'due', NULL),
(14, 1, 14, '2026-11-04', 616.46, 129.65, 746.11, 'due', NULL),
(15, 1, 15, '2026-12-04', 621.60, 124.51, 746.11, 'due', NULL),
(16, 1, 16, '2027-01-04', 626.78, 119.33, 746.11, 'due', NULL),
(17, 1, 17, '2027-02-04', 632.00, 114.11, 746.11, 'due', NULL),
(18, 1, 18, '2027-03-04', 637.27, 108.84, 746.11, 'paid', '2025-09-04 22:50:48'),
(19, 1, 19, '2027-04-04', 642.58, 103.53, 746.11, 'due', NULL),
(20, 1, 20, '2027-05-04', 647.94, 98.17, 746.11, 'due', NULL),
(21, 1, 21, '2027-06-04', 653.34, 92.77, 746.11, 'due', NULL),
(22, 1, 22, '2027-07-04', 658.78, 87.33, 746.11, 'due', NULL),
(23, 1, 23, '2027-08-04', 664.27, 81.84, 746.11, 'due', NULL),
(24, 1, 24, '2027-09-04', 669.81, 76.30, 746.11, 'due', NULL),
(25, 1, 25, '2027-10-04', 675.39, 70.72, 746.11, 'due', NULL),
(26, 1, 26, '2027-11-04', 681.02, 65.09, 746.11, 'due', NULL),
(27, 1, 27, '2027-12-04', 686.69, 59.42, 746.11, 'due', NULL),
(28, 1, 28, '2028-01-04', 692.41, 53.70, 746.11, 'due', NULL),
(29, 1, 29, '2028-02-04', 698.18, 47.93, 746.11, 'due', NULL),
(30, 1, 30, '2028-03-04', 704.00, 42.11, 746.11, 'due', NULL),
(31, 1, 31, '2028-04-04', 709.87, 36.24, 746.11, 'due', NULL),
(32, 1, 32, '2028-05-04', 715.78, 30.33, 746.11, 'due', NULL),
(33, 1, 33, '2028-06-04', 721.75, 24.36, 746.11, 'due', NULL),
(34, 1, 34, '2028-07-04', 727.76, 18.35, 746.11, 'due', NULL),
(35, 1, 35, '2028-08-04', 733.83, 12.28, 746.11, 'due', NULL),
(36, 1, 36, '2028-09-04', 740.10, 6.17, 746.27, 'due', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_price` decimal(12,2) NOT NULL,
  `down_payment` decimal(12,2) NOT NULL,
  `principal` decimal(12,2) NOT NULL,
  `annual_interest_rate` decimal(5,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `car_id`, `car_price`, `down_payment`, `principal`, `annual_interest_rate`, `term_months`, `status`, `admin_note`, `created_at`, `approved_at`) VALUES
(1, 2, 4, 123123.00, 100000.00, 23123.00, 10.00, 36, 'approved', 'goods', '2025-09-04 14:50:02', '2025-09-04 14:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `maintenance_type` enum('routine','repair','inspection','emergency') NOT NULL,
  `description` text NOT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `maintenance_date` date NOT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `status` enum('completed','pending','cancelled') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','reminder') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(1, 3, 'asdasd', 'asdasd', 'warning', 0, '2025-10-03 21:05:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `emi_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `emi_id`, `amount`, `method`, `created_at`) VALUES
(1, 18, 746.11, 'manual', '2025-09-04 14:50:48'),
(2, 1, 746.11, 'manual', '2025-09-04 14:50:50'),
(3, 2, 746.11, 'manual', '2025-09-04 14:50:51'),
(4, 3, 746.11, 'manual', '2025-09-04 14:50:52'),
(5, 4, 746.11, 'manual', '2025-09-04 14:50:52'),
(6, 5, 746.11, 'manual', '2025-09-04 14:50:52'),
(7, 6, 746.11, 'manual', '2025-09-04 15:12:49'),
(8, 7, 746.11, 'manual', '2025-09-04 15:13:03'),
(9, 8, 746.11, 'cash', '2025-09-04 15:16:07');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'manual',
  `status` enum('active','cancelled') DEFAULT 'active',
  `file_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('loan_summary','payment_history','customer_analysis','financial_summary','maintenance_summary') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `report_data` longtext NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_type`, `title`, `description`, `generated_by`, `report_data`, `file_path`, `generated_at`) VALUES
(1, 'payment_history', 'asd', 'asdasd', 1, '[{\"payment_date\":\"2025-09-04\",\"payment_count\":\"9\",\"total_amount\":\"6714.99\"}]', NULL, '2025-10-03 23:27:25');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'company_name', 'Car Loan Management System', 'Company name for the system', '2025-10-03 21:04:29'),
(2, 'max_loan_amount', '5000000', 'Maximum loan amount allowed', '2025-10-03 21:04:29'),
(3, 'min_down_payment_percentage', '20', 'Minimum down payment percentage', '2025-10-03 21:04:29'),
(4, 'late_payment_fee', '500', 'Late payment fee amount', '2025-10-03 21:04:29'),
(5, 'notification_email', 'admin@example.com', 'System notification email', '2025-10-03 21:04:29'),
(6, 'maintenance_reminder_days', '30', 'Days before maintenance reminder', '2025-10-03 21:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `blocked` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `blocked`, `email`, `created_at`) VALUES
(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Administrator', 'admin', 0, 'admin@example.com', '2025-09-04 14:42:04'),
(2, 'sandy', '$2y$10$BGAHOlRW1oi9K5/PMoVqou.hphBB37ml/BQLaMe4UzsMtKVg8C7ie', 'sandy jean fabria', 'customer', 0, 'ahdahsdh@gmail.com', '2025-09-04 14:49:27'),
(3, 'staffs', '$2y$10$s8hERq6tc.8g2AzXnDG14ujwCi2v87GZI6z0HfYDpCjOyn7WqWgKm', 'staff staff', 'customer', 0, 'staff@gmail.com', '2025-10-03 13:11:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appeals`
--
ALTER TABLE `appeals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `table_name` (`table_name`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_no` (`plate_no`);

--
-- Indexes for table `car_insurance`
--
ALTER TABLE `car_insurance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `emis`
--
ALTER TABLE `emis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emi_id` (`emi_id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appeals`
--
ALTER TABLE `appeals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `car_insurance`
--
ALTER TABLE `car_insurance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emis`
--
ALTER TABLE `emis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appeals`
--
ALTER TABLE `appeals`
  ADD CONSTRAINT `appeals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `car_insurance`
--
ALTER TABLE `car_insurance`
  ADD CONSTRAINT `car_insurance_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `emis`
--
ALTER TABLE `emis`
  ADD CONSTRAINT `emis_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Constraints for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `maintenance_records_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`emi_id`) REFERENCES `emis` (`id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receipts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
