-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 09:45 PM
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
-- Database: `car_loan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `id_number` varchar(60) DEFAULT NULL,
  `customer_type` enum('buyer','loaner') NOT NULL DEFAULT 'buyer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `address`, `id_number`, `customer_type`, `created_at`) VALUES
(1, 'John', NULL, 'Doe', 'johndoe@example.com', '+1-555-1000', '123 Main St, City', 'ID-0001', 'buyer', '2025-09-04 12:57:25'),
(2, 'Jane', NULL, 'Smith', 'jane@example.com', '+1-555-2000', '456 Oak Ave, City', 'ID-0002', 'buyer', '2025-09-04 12:57:25'),
(3, 'Carlos', NULL, 'Reyes', 'carlos@example.com', '+1-555-3000', '789 Pine Rd, City', 'ID-0003', 'buyer', '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_number` varchar(32) NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `vehicle_id` int(10) UNSIGNED NOT NULL,
  `principal` decimal(12,2) NOT NULL,
  `down_payment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(5,2) NOT NULL,
  `term_months` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `payment_frequency` enum('monthly') NOT NULL DEFAULT 'monthly',
  `status` enum('pending','active','completed','defaulted','cancelled') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `loan_number`, `customer_id`, `vehicle_id`, `principal`, `down_payment`, `interest_rate`, `term_months`, `start_date`, `payment_frequency`, `status`, `created_by`, `created_at`) VALUES
(1, 'LN-0001', 1, 1, 10000.00, 2000.00, 12.00, 24, '2025-01-01', 'monthly', 'active', 1, '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `loan_schedules`
--

CREATE TABLE `loan_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `installment_no` int(10) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `amount_principal` decimal(12,2) NOT NULL,
  `amount_interest` decimal(12,2) NOT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('due','partial','paid','overdue') NOT NULL DEFAULT 'due',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_schedules`
--

INSERT INTO `loan_schedules` (`id`, `loan_id`, `installment_no`, `due_date`, `amount_principal`, `amount_interest`, `amount_due`, `paid_amount`, `status`, `created_at`) VALUES
(1, 1, 1, '2025-02-01', 333.33, 100.00, 433.33, 0.00, 'due', '2025-09-04 12:57:25'),
(2, 1, 2, '2025-03-01', 333.33, 95.00, 428.33, 0.00, 'due', '2025-09-04 12:57:25'),
(3, 1, 3, '2025-04-01', 333.34, 90.00, 423.34, 0.00, 'due', '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `schedule_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('cash','bank','mobile','card','other') NOT NULL DEFAULT 'cash',
  `reference` varchar(100) DEFAULT NULL,
  `received_by` int(10) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `loan_id`, `schedule_id`, `payment_date`, `amount`, `method`, `reference`, `received_by`, `notes`, `created_at`) VALUES
(1, 1, 1, '2025-02-01', 433.33, 'cash', 'RCPT-1001', 3, 'First installment fully paid', '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `role` enum('admin','manager','staff','buyer') NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `email`, `role`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 'admin', 'Admin User', 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-09-04 12:57:25'),
(2, 'manager', 'Manager User', 'manager@example.com', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-09-04 12:57:25'),
(3, 'staff', 'Staff User', 'staff@example.com', 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-09-04 12:57:25'),
(4, 'buyer', 'Buyer User', 'buyer@example.com', 'buyer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(10) UNSIGNED NOT NULL,
  `vin` varchar(32) DEFAULT NULL,
  `make` varchar(60) NOT NULL,
  `model` varchar(80) NOT NULL,
  `vehicle_year` smallint(5) UNSIGNED NOT NULL,
  `color` varchar(40) DEFAULT NULL,
  `mileage` int(10) UNSIGNED DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `status` enum('available','reserved','sold','inactive') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vin`, `make`, `model`, `vehicle_year`, `color`, `mileage`, `price`, `status`, `created_at`) VALUES
(1, '1HGBH41JXMN109186', 'Toyota', 'Corolla', 2020, 'White', 32000, 12000.00, 'available', '2025-09-04 12:57:25'),
(2, '1M8GDM9AXKP042788', 'Honda', 'Civic', 2019, 'Black', 41000, 11000.00, 'available', '2025-09-04 12:57:25'),
(3, '3C6UR5CJ3HG123456', 'Nissan', 'Sentra', 2021, 'Silver', 21000, 13500.00, 'available', '2025-09-04 12:57:25');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_buyers`
-- (See below for the actual view)
--
CREATE TABLE `v_buyers` (
`id` int(10) unsigned
,`first_name` varchar(80)
,`middle_name` varchar(80)
,`last_name` varchar(80)
,`email` varchar(120)
,`phone` varchar(30)
,`address` varchar(255)
,`id_number` varchar(60)
,`customer_type` enum('buyer','loaner')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_loaners`
-- (See below for the actual view)
--
CREATE TABLE `v_loaners` (
`id` int(10) unsigned
,`first_name` varchar(80)
,`middle_name` varchar(80)
,`last_name` varchar(80)
,`email` varchar(120)
,`phone` varchar(30)
,`address` varchar(255)
,`id_number` varchar(60)
,`customer_type` enum('buyer','loaner')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_loan_balances`
-- (See below for the actual view)
--
CREATE TABLE `v_loan_balances` (
`loan_id` int(10) unsigned
,`loan_number` varchar(32)
,`customer_id` int(10) unsigned
,`vehicle_id` int(10) unsigned
,`principal` decimal(12,2)
,`down_payment` decimal(12,2)
,`interest_rate` decimal(5,2)
,`term_months` int(10) unsigned
,`status` enum('pending','active','completed','defaulted','cancelled')
,`total_due` decimal(34,2)
,`total_paid` decimal(34,2)
,`balance_remaining` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_buyers`
--
DROP TABLE IF EXISTS `v_buyers`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_buyers`  AS SELECT `customers`.`id` AS `id`, `customers`.`first_name` AS `first_name`, `customers`.`middle_name` AS `middle_name`, `customers`.`last_name` AS `last_name`, `customers`.`email` AS `email`, `customers`.`phone` AS `phone`, `customers`.`address` AS `address`, `customers`.`id_number` AS `id_number`, `customers`.`customer_type` AS `customer_type`, `customers`.`created_at` AS `created_at` FROM `customers` WHERE `customers`.`customer_type` = 'buyer' ;

-- --------------------------------------------------------

--
-- Structure for view `v_loaners`
--
DROP TABLE IF EXISTS `v_loaners`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_loaners`  AS SELECT `customers`.`id` AS `id`, `customers`.`first_name` AS `first_name`, `customers`.`middle_name` AS `middle_name`, `customers`.`last_name` AS `last_name`, `customers`.`email` AS `email`, `customers`.`phone` AS `phone`, `customers`.`address` AS `address`, `customers`.`id_number` AS `id_number`, `customers`.`customer_type` AS `customer_type`, `customers`.`created_at` AS `created_at` FROM `customers` WHERE `customers`.`customer_type` = 'loaner' ;

-- --------------------------------------------------------

--
-- Structure for view `v_loan_balances`
--
DROP TABLE IF EXISTS `v_loan_balances`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_loan_balances`  AS SELECT `l`.`id` AS `loan_id`, `l`.`loan_number` AS `loan_number`, `l`.`customer_id` AS `customer_id`, `l`.`vehicle_id` AS `vehicle_id`, `l`.`principal` AS `principal`, `l`.`down_payment` AS `down_payment`, `l`.`interest_rate` AS `interest_rate`, `l`.`term_months` AS `term_months`, `l`.`status` AS `status`, coalesce(sum(`ls`.`amount_due`),0) AS `total_due`, coalesce(sum(`ls`.`paid_amount`),0) AS `total_paid`, coalesce(sum(`ls`.`amount_due`) - sum(`ls`.`paid_amount`),0) AS `balance_remaining` FROM (`loans` `l` left join `loan_schedules` `ls` on(`ls`.`loan_id` = `l`.`id`)) GROUP BY `l`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_number` (`loan_number`),
  ADD KEY `fk_loans_customer` (`customer_id`),
  ADD KEY `fk_loans_vehicle` (`vehicle_id`),
  ADD KEY `fk_loans_creator` (`created_by`);

--
-- Indexes for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loan_installment` (`loan_id`,`installment_no`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_loan` (`loan_id`),
  ADD KEY `idx_payments_schedule` (`schedule_id`),
  ADD KEY `fk_payments_user` (`received_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vin` (`vin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_loans_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_loans_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `loan_schedules`
--
ALTER TABLE `loan_schedules`
  ADD CONSTRAINT `fk_schedules_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `fk_payments_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `loan_schedules` (`id`),
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
