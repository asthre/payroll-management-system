-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 11:21 AM
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
-- Database: `payroll_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_payroll_entries` ()   BEGIN
  DECLARE i INT DEFAULT 1;

  WHILE i <= 1000 DO
    INSERT INTO payroll_entries (
      payroll_month, payroll_number, last_name, first_name, middle_name, suffix,
      department, sss, amount, created_at, created_by, updated_at, updated_by
    ) VALUES (
      DATE_FORMAT(NOW(), '%Y-%m'),
      CONCAT('PN-', LPAD(i, 4, '0')),
      CONCAT('Last', i),
      CONCAT('First', i),
      CONCAT('Middle', i),
      IF(i % 5 = 0, 'Jr.', NULL),
      IF(i % 3 = 0, 'HR', 'MITCS'),
      ROUND(RAND() * 1000, 2),
      ROUND(RAND() * 100000, 2),
      NOW(),
      'rhenzal',
      NOW(),
      NULL
    );
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_entries`
--

CREATE TABLE `payroll_entries` (
  `id` int(11) NOT NULL,
  `payroll_month` varchar(7) NOT NULL,
  `payroll_number` varchar(50) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `sss` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'uploads/default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `status`, `created_at`, `profile_picture`) VALUES
(1, 'rhenzal', '$2y$10$MclWtl6RX8Qycu3gBHBfJ.UEIHqmXGtUWQUSuC.372IGTLECb1Ram', 'active', '2025-08-03 06:26:35', 'uploads/default_avatar.jpg'),
(2, 'ej', '$2y$10$2NCH6PeRUvcrnyF06dRKwuWVUfu7b32tSn4JsR.6b31ZGcC9UWDHi', 'active', '2025-08-05 09:16:07', 'uploads/default_avatar.jpg'),
(3, 'michael', '$2y$10$xZZ7wjMzkWUdT8uu7icoJ.1YEBThhM6ll4NDQkrTqxKToDemmrvUS', 'active', '2025-08-05 09:17:31', 'uploads/default_avatar.jpg'),
(4, 'kevin', '$2y$10$WrLIy7LEVboJmy8QY1n6QeRSuS3varm.l425v7ZsFUAAt0EiJm.he', 'active', '2025-08-05 09:18:23', 'uploads/default_avatar.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll_entry` (`first_name`,`last_name`,`middle_name`,`payroll_month`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
