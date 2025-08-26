-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 04:01 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rsn_wmt_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Web'),
(2, 'Ancilliary'),
(3, 'Fraud Detection'),
(4, 'Nectar');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_amendments`
--

CREATE TABLE `dtr_amendments` (
  `id` int(11) NOT NULL,
  `request_uid` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `log_id` int(11) NOT NULL,
  `field` varchar(50) NOT NULL,
  `old_value` varchar(255) NOT NULL,
  `new_value` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_amendments`
--

INSERT INTO `dtr_amendments` (`id`, `request_uid`, `user_id`, `recipient_id`, `log_id`, `field`, `old_value`, `new_value`, `reason`, `status`, `processed_by`, `processed_at`, `requested_at`) VALUES
(1, 'REQ-A090B99A916F', 1, 2, 9, 'start_time', '09:30:00', '09:35', 'checking if buttons will disable after one action', 'Approved', 2, '2025-08-18 18:56:48', '2025-08-18 17:58:53');

-- --------------------------------------------------------

--
-- Table structure for table `task_descriptions`
--

CREATE TABLE `task_descriptions` (
  `id` int(11) NOT NULL,
  `work_mode_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_descriptions`
--

INSERT INTO `task_descriptions` (`id`, `work_mode_id`, `description`) VALUES
(1, 1, 'Web - Web Content'),
(3, 1, 'Web - Client Meeting'),
(4, 1, 'Web - Holiday Content'),
(5, 1, 'Training - Web'),
(6, 1, 'Offphone - Coaching Web'),
(7, 1, 'Offphone - Team Huddle.Web'),
(10, 2, 'Away - Break'),
(11, 2, 'End Shift'),
(12, 2, 'Resono - Management Training'),
(13, 2, 'Resono - Office Duty'),
(14, 2, 'Resono - One on One'),
(15, 2, 'Resono - Team Meeting Function');

-- --------------------------------------------------------

--
-- Table structure for table `task_logs`
--

CREATE TABLE `task_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_mode_id` int(11) NOT NULL,
  `task_description_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `total_duration` time DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_logs`
--

INSERT INTO `task_logs` (`id`, `user_id`, `work_mode_id`, `task_description_id`, `date`, `start_time`, `end_time`, `total_duration`, `remarks`) VALUES
(1, 2, 1, 1, '2025-07-23', '18:14:16', '18:26:22', '00:12:06', ''),
(2, 2, 2, 10, '2025-07-23', '18:26:22', '18:43:23', '00:17:01', ''),
(3, 2, 1, 1, '2025-07-23', '18:43:23', '18:53:31', '00:10:08', ''),
(4, 2, 2, 11, '2025-07-23', '18:53:31', NULL, NULL, ''),
(5, 2, 1, 1, '2025-07-24', '19:18:00', '19:33:27', '00:15:27', ''),
(6, 2, 2, 10, '2025-07-24', '19:33:27', '20:28:55', '00:55:28', ''),
(7, 2, 1, 1, '2025-07-24', '20:28:55', '20:34:50', '00:05:55', ''),
(8, 2, 2, 11, '2025-07-24', '20:34:50', NULL, NULL, ''),
(9, 1, 1, 1, '2025-08-11', '09:35:00', '09:56:52', '00:21:52', 'Fraud queue review'),
(10, 1, 2, 10, '2025-08-11', '09:56:52', '10:27:47', '00:30:55', ''),
(11, 1, 1, 1, '2025-08-11', '10:27:47', NULL, NULL, ''),
(12, 1, 2, 11, '2025-08-16', '22:11:38', NULL, NULL, ''),
(13, 2, 1, 1, '2025-08-17', '03:12:58', '03:43:38', '00:30:40', ''),
(14, 2, 2, 10, '2025-08-17', '03:43:38', '12:52:34', '09:08:56', ''),
(15, 2, 1, 1, '2025-08-17', '12:52:34', '12:52:46', '00:00:12', ''),
(16, 2, 2, 11, '2025-08-17', '12:52:46', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','executive','user','hr') NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `is_online`, `created_at`, `department_id`) VALUES
(1, '', 'Joeanalyn', 'Diaz', 'Grande', 'joeanalyn07@gmail.com', '$2y$10$83z7D.cnf8oPSLBq1t2p8ePX6ixbdRWJpziLM0w3yc2u92wQJxjqG', 'user', 1, '2025-07-14 05:19:25', 1),
(2, NULL, 'Mark Francis', 'Perez', 'De Guzman', 'deguzmanmarkfrancisp@gmail.com', '$2y$10$Ku0SC0rEMe6hYceFve5CYuUBMVIokiFiyOcqkm0omNIXaCHp5UL.q', 'admin', 1, '2025-07-14 05:20:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `work_modes`
--

CREATE TABLE `work_modes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_modes`
--

INSERT INTO `work_modes` (`id`, `name`) VALUES
(1, 'Web Content'),
(2, 'Away-Time'),
(3, 'Nectar');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dtr_amendments`
--
ALTER TABLE `dtr_amendments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_uid` (`request_uid`);

--
-- Indexes for table `task_descriptions`
--
ALTER TABLE `task_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_mode_id` (`work_mode_id`);

--
-- Indexes for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `work_mode_id` (`work_mode_id`),
  ADD KEY `task_description_id` (`task_description_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_department` (`department_id`);

--
-- Indexes for table `work_modes`
--
ALTER TABLE `work_modes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `dtr_amendments`
--
ALTER TABLE `dtr_amendments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_descriptions`
--
ALTER TABLE `task_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_modes`
--
ALTER TABLE `work_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `task_descriptions`
--
ALTER TABLE `task_descriptions`
  ADD CONSTRAINT `task_descriptions_ibfk_1` FOREIGN KEY (`work_mode_id`) REFERENCES `work_modes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_logs`
--
ALTER TABLE `task_logs`
  ADD CONSTRAINT `task_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_logs_ibfk_2` FOREIGN KEY (`work_mode_id`) REFERENCES `work_modes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_logs_ibfk_3` FOREIGN KEY (`task_description_id`) REFERENCES `task_descriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
