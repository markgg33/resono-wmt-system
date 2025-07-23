-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 06:01 AM
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
(1, 1, 1, 1, '2025-07-10', '13:52:06', '13:52:25', '00:00:25', ''),
(2, 1, 2, 10, '2025-07-10', '13:52:25', '13:53:36', '00:01:36', ''),
(3, 1, 1, 1, '2025-07-10', '13:53:36', '23:36:33', '09:42:57', ''),
(4, 1, 2, 11, '2025-07-11', '23:36:33', NULL, NULL, '');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `is_online`, `created_at`) VALUES
(1, '', 'Mark Francis', 'Perez', 'De Guzman', 'deguzmanmarkfrancisp@gmail.com', '$2y$10$KFXtv1nJr4CUW81lSVpi4.CNchdgi6I47DP./zRIDN/2wSn6vSa2O', 'admin', 0, '2025-07-09 00:51:17');

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
(2, 'Away-Time');

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `work_modes`
--
ALTER TABLE `work_modes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `task_descriptions`
--
ALTER TABLE `task_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_modes`
--
ALTER TABLE `work_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
