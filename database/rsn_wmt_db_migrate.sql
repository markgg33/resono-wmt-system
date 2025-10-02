-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2025 at 07:02 AM
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
(2, 'Ancillary'),
(3, 'Fraud Detection'),
(4, 'Nectar Brand'),
(6, 'Technology and Data'),
(7, 'Management');

-- --------------------------------------------------------

--
-- Table structure for table `department_work_modes`
--

CREATE TABLE `department_work_modes` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `work_mode_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_work_modes`
--

INSERT INTO `department_work_modes` (`id`, `department_id`, `work_mode_id`) VALUES
(4, 1, 1),
(1, 2, 4);

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
(1, 'REQ-A090B99A916F', 1, 2, 9, 'start_time', '09:30:00', '09:35', 'checking if buttons will disable after one action', 'Approved', 2, '2025-08-18 18:56:48', '2025-08-18 17:58:53'),
(2, 'REQ-32390A865CCB', 1, 2, 10, 'start_time', '09:56:52', '09:30', 'sample request', 'Rejected', 2, '2025-08-19 22:06:22', '2025-08-19 14:54:53'),
(3, 'REQ-D39C01B7029F', 1, 2, 10, 'start_time', '09:56:52', '09:30', 'sample', 'Rejected', 2, '2025-08-19 22:56:39', '2025-08-19 21:48:41'),
(4, 'REQ-59F1A3175222', 1, 2, 10, 'end_time', '10:27:47', '10:30', 'exact time of end time', 'Approved', 2, '2025-08-19 22:56:45', '2025-08-19 22:56:16'),
(5, 'REQ-3164B8C284CF', 2, 2, 20, 'end_time', '12:35:54', '12:34', 'One minute overbreak', 'Approved', 2, '2025-08-20 12:37:19', '2025-08-20 12:37:00'),
(6, 'REQ-ED84E65467A4', 2, 2, 21, 'start_time', '12:35:54', '12:34', 'start time adjustment', 'Approved', 2, '2025-08-20 12:38:11', '2025-08-20 12:37:51'),
(7, 'REQ-A2FBD542E83B', 1, 2, 10, 'end_time', '10:30:00', '10:27', 'match the next task\'s start time', 'Pending', NULL, NULL, '2025-08-20 13:22:21'),
(8, 'REQ-2A18B65E149C', 1, 2, 10, 'end_time', '10:30:00', '10:43', 'sample ', 'Approved', 2, '2025-08-25 08:28:02', '2025-08-25 08:27:24'),
(9, 'REQ-8957161BAFFC', 1, 2, 10, 'end_time', '10:43:00', '10:45', 'new sample to see if next task changes', 'Approved', 2, '2025-08-25 10:15:14', '2025-08-25 10:14:52'),
(10, 'REQ-FBCA22ED9102', 2, 4, 14, 'end_time', '12:52:34', '04:52', 'sample changes end time', 'Approved', 4, '2025-08-25 10:19:18', '2025-08-25 10:18:33'),
(11, 'REQ-BDC9140E64F8', 1, 2, 29, 'start_time', '12:35:13', '12:30', 'sample', 'Approved', 2, '2025-08-25 12:39:26', '2025-08-25 12:37:31'),
(12, 'REQ-BBB470362B60', 6, 6, 58, 'end_time', '--', '17:00', 'forgot to toggle', 'Approved', 6, '2025-09-01 16:12:51', '2025-09-01 16:11:59'),
(13, 'REQ-9A13FA30725C', 2, 4, 95, 'end_time', '13:14:00', '13:11', '3 minutes late tag', 'Approved', 4, '2025-09-06 19:02:27', '2025-09-02 23:18:16'),
(14, 'REQ-24AABFDBD03E', 8, 4, 118, 'start_time', '06:01:58', '06:00', 'Laptop blackout while opening browser.', 'Approved', 4, '2025-09-06 19:01:53', '2025-09-03 15:06:08'),
(15, 'REQ-0F682C3E085E', 4, 6, 151, 'start_time', '08:14:10', '08:00', 'System Testing', 'Pending', NULL, NULL, '2025-09-07 17:15:06'),
(16, 'REQ-89AA9D0552FC', 8, 4, 171, 'start_time', '11:34:06', '06:00', 'Forgot to tag/use this WMT login.', 'Rejected', 22, '2025-09-30 21:10:53', '2025-09-08 21:49:36'),
(17, 'REQ-C4E3B8C4C876', 8, 4, 175, 'start_time', '13:08:13', '13:00', 'WMT error. 502 Bad Gateway error.', 'Rejected', 22, '2025-09-30 21:10:56', '2025-09-08 22:10:19'),
(18, 'REQ-FA83D8DF9706', 8, 4, 175, 'start_time', '13:08:13', '13:00', 'WMT error. 502 Bad Gateway error.', 'Rejected', 22, '2025-09-30 21:10:48', '2025-09-08 22:10:19'),
(19, 'REQ-6313EB4497CC', 2, 2, 232, 'date', 'Sep 14, 2025', '2025-09-13', 'End shift amendment sample', 'Approved', 2, '2025-09-14 10:56:39', '2025-09-14 10:56:00'),
(20, 'REQ-E3E3AD2CC18F', 2, 2, 232, 'date', 'Sep 13, 2025', '2025-09-14', 'revert date', 'Approved', 2, '2025-09-14 14:06:41', '2025-09-14 14:06:28'),
(21, 'REQ-7B4C5DE59158', 2, 16, 240, 'start_time', '21:49:33', '21:55', 'sample multi dept request', 'Rejected', 16, '2025-09-24 19:02:00', '2025-09-24 18:45:03'),
(22, 'REQ-F7DF3F662735', 2, 16, 240, 'start_time', '21:49:33', '21:55', 'sample', 'Rejected', 16, '2025-09-24 20:50:18', '2025-09-24 19:30:17'),
(23, 'REQ-5847EDC999AC', 2, 16, 241, 'start_time', '21:49:39', '21:55', 'sample', 'Rejected', 16, '2025-09-25 05:49:31', '2025-09-24 20:46:23'),
(24, 'REQ-3B18370A0CE1', 2, 22, 44, 'start_time', '06:00:16', '06:30', 'sample', 'Rejected', 22, '2025-09-27 21:15:56', '2025-09-27 21:15:46'),
(27, 'REQ-9BD647356B4E', 2, 14, 244, 'date', '||', '2025-09-19|20:06|', 'SAMPLE', 'Rejected', 14, '2025-09-29 22:48:26', '2025-09-29 22:15:01'),
(28, 'REQ-EA952DC1C80C', 2, 14, 244, 'date', '2025-09-29|20:06:36|', '2025-09-19|20:30|', 'SAMPLE', 'Approved', 14, '2025-09-30 00:39:06', '2025-09-29 22:47:54'),
(29, 'REQ-650A1BA247C2', 2, 14, 243, 'start_time', '22:23:14', '22:30', 'SAMPLE FOR NEW SINGLE AMENDMENT', 'Approved', 14, '2025-09-29 22:49:12', '2025-09-29 22:49:02'),
(30, 'REQ-6BF6E5101354', 2, 14, 240, 'end_time', '21:49:39', '21:59', 'SAMPLE FOR END TIME ', 'Approved', 14, '2025-09-29 22:49:52', '2025-09-29 22:49:45'),
(31, 'REQ-E954AB26A647', 2, 14, 232, 'date', '2025-09-14|00:25:18|', '2025-09-13|00:25|', 'Testing', 'Approved', 14, '2025-09-30 00:37:48', '2025-09-29 23:13:42'),
(32, 'REQ-953440A9A35A', 2, 14, 240, 'end_time', '21:59:00', '22:00', 'single sample', 'Approved', 14, '2025-09-30 00:08:29', '2025-09-30 00:08:00'),
(33, 'REQ-9CB861D8E8FE', 2, 14, 245, 'date', '2025-09-30|19:11:11|', '2025-09-19|19:11|', 'sample amendment', 'Approved', 10, '2025-09-30 20:52:25', '2025-09-30 20:50:57'),
(34, 'REQ-83909FAE702E', 2, 22, 245, 'date', '2025-09-19|19:11:00|', '2025-09-22|06:00|', '6am start testing', 'Pending', NULL, NULL, '2025-09-30 20:53:30'),
(35, 'REQ-C38C17257A76', 2, 14, 243, 'end_time', '', '20:30', 'END TIME ', 'Pending', NULL, NULL, '2025-09-30 21:15:49'),
(36, 'REQ-703BD4AF2DEB', 2, 14, 78, 'start_time', '16:20:31', '16:39', 'recipient and pagination testing', 'Pending', NULL, NULL, '2025-09-30 21:26:49'),
(37, 'REQ-45EF1A03180D', 2, 14, 243, 'end_time', '', '20:30', 'sample', 'Pending', NULL, NULL, '2025-09-30 22:11:53');

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
(15, 2, 'Resono - Team Meeting Function'),
(16, 4, 'Ancillary - Adhoc'),
(17, 4, 'Ancillary - EK Seating'),
(18, 4, 'Ancillary - XML'),
(19, 4, 'Ancillary - Frequent Flyer'),
(20, 4, 'Ancillary - Meal SSR Task'),
(21, 4, 'Ancillary - Test Booking'),
(22, 4, 'Ancillary - Bundle VO Monitoring'),
(23, 4, 'Ancillary - FF Inbox'),
(24, 4, 'Training - Ancillary'),
(25, 4, 'Offphone - Coaching.Ancillary'),
(26, 4, 'Offphone - Team Huddle.Ancillary'),
(27, 4, 'Ancillary - Brand Extension - CJ'),
(28, 5, 'System Testing'),
(29, 5, 'Ticket Creation'),
(30, 5, 'Team Huddle'),
(31, 6, 'Meeting - Client'),
(32, 6, 'Meeting - Internal'),
(33, 6, 'Project Management');

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
  `remarks` text DEFAULT NULL,
  `volume_remark` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_logs`
--

INSERT INTO `task_logs` (`id`, `user_id`, `work_mode_id`, `task_description_id`, `date`, `start_time`, `end_time`, `total_duration`, `remarks`, `volume_remark`) VALUES
(44, 2, 1, 1, '2025-09-01', '06:00:16', '11:30:10', '05:29:54', '', NULL),
(45, 9, 1, 1, '2025-09-01', '06:00:22', '11:07:33', '05:07:11', '', NULL),
(46, 8, 1, 1, '2025-09-01', '06:00:28', '11:08:30', '05:08:58', '', NULL),
(48, 6, 5, 28, '2025-09-01', '07:52:09', '12:48:42', '04:56:33', '', NULL),
(49, 8, 2, 10, '2025-09-01', '11:08:30', '12:36:14', '01:27:44', '', NULL),
(50, 9, 2, 10, '2025-09-01', '11:07:33', '12:22:29', '01:14:56', '', NULL),
(51, 2, 2, 10, '2025-09-01', '11:30:10', '13:00:13', '01:30:03', '', NULL),
(52, 9, 1, 1, '2025-09-01', '12:22:29', '13:50:38', '01:28:09', '', NULL),
(53, 8, 1, 1, '2025-09-01', '12:36:14', '15:00:31', '02:24:17', '', NULL),
(54, 6, 2, 10, '2025-09-01', '12:48:42', '14:55:45', '02:07:03', '', NULL),
(55, 2, 1, 1, '2025-09-01', '13:00:13', '15:00:03', '01:59:50', '', NULL),
(56, 9, 2, 10, '2025-09-01', '13:50:38', '14:01:35', '00:10:57', '', NULL),
(57, 9, 1, 1, '2025-09-01', '14:01:35', '15:00:17', '00:58:42', '', NULL),
(58, 6, 5, 28, '2025-09-01', '14:55:45', '17:00:00', '02:04:15', '', NULL),
(59, 2, 2, 10, '2025-09-01', '15:00:03', '16:45:03', '01:45:00', '', NULL),
(60, 9, 2, 11, '2025-09-01', '15:00:17', NULL, NULL, '', NULL),
(61, 8, 2, 11, '2025-09-01', '15:00:31', NULL, NULL, '', NULL),
(62, 2, 2, 13, '2025-09-01', '16:45:03', '19:45:32', '03:00:29', 'Tech Duty', NULL),
(63, 2, 2, 11, '2025-09-01', '19:45:32', NULL, NULL, '', NULL),
(64, 2, 1, 1, '2025-09-02', '06:00:07', '11:30:16', '05:30:09', '', NULL),
(65, 9, 1, 1, '2025-09-02', '06:00:08', '11:04:29', '05:04:21', '', NULL),
(66, 8, 1, 1, '2025-09-02', '06:00:12', '11:05:12', '05:05:00', '', NULL),
(67, 6, 2, 11, '2025-09-02', '17:00:00', '07:13:34', '00:00:00', '', NULL),
(68, 6, 5, 28, '2025-09-02', '07:13:34', '18:29:04', '11:15:30', '', NULL),
(69, 9, 2, 10, '2025-09-02', '11:04:29', '12:26:46', '01:22:17', '', NULL),
(70, 8, 2, 10, '2025-09-02', '11:05:12', '12:30:17', '01:25:05', '', NULL),
(71, 2, 2, 10, '2025-09-02', '11:30:16', '13:00:23', '01:30:07', '', NULL),
(72, 9, 1, 1, '2025-09-02', '12:26:46', '15:00:24', '02:33:38', '', NULL),
(73, 8, 1, 1, '2025-09-02', '12:30:17', '15:01:04', '02:30:47', '', NULL),
(74, 2, 1, 1, '2025-09-02', '13:00:23', '15:00:23', '02:00:00', '', NULL),
(75, 2, 2, 10, '2025-09-02', '15:00:23', '16:20:31', '01:20:08', '', NULL),
(76, 9, 2, 11, '2025-09-02', '15:00:24', NULL, NULL, '', NULL),
(77, 8, 2, 11, '2025-09-02', '15:01:04', NULL, NULL, '', NULL),
(78, 2, 2, 13, '2025-09-02', '16:20:31', '17:39:09', '01:18:38', 'Tech Duty', NULL),
(79, 2, 2, 11, '2025-09-02', '17:39:09', NULL, NULL, '', NULL),
(80, 6, 2, 11, '2025-09-02', '18:29:04', NULL, NULL, '', NULL),
(81, 2, 1, 1, '2025-09-03', '06:00:10', '07:15:18', '01:15:08', '', NULL),
(82, 9, 1, 1, '2025-09-03', '06:00:12', '10:45:26', '04:45:14', '', NULL),
(83, 8, 1, 1, '2025-09-03', '06:01:56', '10:46:33', '04:44:37', '', NULL),
(84, 2, 2, 10, '2025-09-03', '07:15:30', '07:32:07', '00:16:37', '', NULL),
(85, 2, 1, 1, '2025-09-03', '07:32:07', '11:49:02', '04:16:55', '', NULL),
(87, 9, 2, 10, '2025-09-03', '10:45:26', '12:00:41', '01:15:15', '', NULL),
(88, 8, 2, 10, '2025-09-03', '10:46:33', '11:51:45', '01:05:12', '', NULL),
(89, 2, 2, 10, '2025-09-03', '11:49:02', '12:00:02', '00:11:00', '', NULL),
(90, 8, 1, 1, '2025-09-03', '11:52:22', '12:01:09', '00:08:47', '', NULL),
(91, 2, 1, 3, '2025-09-03', '12:00:02', '12:08:55', '00:08:53', '', NULL),
(92, 9, 1, 3, '2025-09-03', '12:00:41', '12:08:51', '00:08:10', '', NULL),
(93, 8, 1, 3, '2025-09-03', '12:01:09', '12:09:48', '00:08:39', '', NULL),
(94, 9, 1, 1, '2025-09-03', '12:08:51', '13:08:17', '00:59:26', '', NULL),
(95, 2, 2, 10, '2025-09-03', '12:08:55', '13:11:00', '01:02:05', '2 minutes late tag due to meeting', NULL),
(96, 8, 1, 1, '2025-09-03', '12:09:48', '13:18:13', '01:08:25', '', NULL),
(98, 9, 2, 10, '2025-09-03', '13:08:17', '13:21:35', '00:13:18', '', NULL),
(99, 2, 1, 1, '2025-09-03', '13:11:00', '15:00:37', '01:49:37', '', NULL),
(100, 8, 2, 10, '2025-09-03', '13:18:13', '13:37:35', '00:19:22', '', NULL),
(101, 9, 1, 1, '2025-09-03', '13:21:35', '15:01:02', '01:39:27', '', NULL),
(102, 8, 1, 1, '2025-09-03', '13:37:35', '15:00:52', '01:23:17', '', NULL),
(104, 2, 2, 10, '2025-09-03', '15:00:37', '16:45:03', '01:44:26', '', NULL),
(105, 8, 2, 11, '2025-09-03', '15:00:52', NULL, NULL, '', NULL),
(106, 9, 2, 11, '2025-09-03', '15:01:02', NULL, NULL, '', NULL),
(107, 2, 2, 13, '2025-09-03', '16:45:03', '19:45:32', '03:00:29', 'Tech Duty', NULL),
(115, 2, 2, 11, '2025-09-03', '19:45:32', NULL, NULL, '', NULL),
(116, 9, 1, 1, '2025-09-04', '06:00:39', '10:56:00', '04:55:21', '', NULL),
(117, 2, 1, 1, '2025-09-04', '06:00:46', '11:41:56', '05:41:10', '', NULL),
(118, 8, 1, 1, '2025-09-04', '06:00:00', '11:24:05', '05:24:05', '', NULL),
(119, 9, 2, 10, '2025-09-04', '10:56:00', '12:18:18', '01:22:18', '', NULL),
(120, 8, 2, 10, '2025-09-04', '11:24:05', '12:50:36', '01:26:31', '', NULL),
(121, 2, 2, 10, '2025-09-04', '11:41:56', '13:11:34', '01:29:38', '', NULL),
(122, 9, 1, 1, '2025-09-04', '12:18:18', '15:00:10', '02:41:52', '', NULL),
(123, 8, 1, 1, '2025-09-04', '12:50:36', '15:00:19', '02:09:43', '', NULL),
(124, 2, 1, 1, '2025-09-04', '13:11:34', '15:00:06', '01:48:32', '', NULL),
(125, 2, 2, 11, '2025-09-04', '15:00:06', NULL, NULL, '', NULL),
(126, 9, 2, 11, '2025-09-04', '15:00:10', NULL, NULL, '', NULL),
(127, 8, 2, 11, '2025-09-04', '15:00:19', NULL, NULL, '', NULL),
(128, 2, 1, 1, '2025-09-05', '06:00:02', '07:15:07', '01:15:05', '', NULL),
(129, 9, 1, 1, '2025-09-05', '06:00:39', '11:02:39', '05:02:00', '', NULL),
(130, 8, 1, 1, '2025-09-05', '06:00:56', '11:37:00', '05:36:04', '', NULL),
(131, 2, 2, 10, '2025-09-05', '07:15:07', '07:29:13', '00:14:06', '', NULL),
(132, 2, 1, 1, '2025-09-05', '07:29:13', '12:00:20', '04:31:07', '', NULL),
(133, 9, 2, 10, '2025-09-05', '11:02:39', '12:32:16', '01:29:37', '', NULL),
(134, 8, 2, 10, '2025-09-05', '11:37:00', '13:05:09', '01:28:09', '', NULL),
(135, 2, 2, 10, '2025-09-05', '12:00:20', '13:11:35', '01:11:15', '', NULL),
(136, 9, 1, 1, '2025-09-05', '12:32:16', '15:00:23', '02:28:07', '', NULL),
(137, 8, 1, 1, '2025-09-05', '13:05:09', '15:01:26', '01:56:17', '', NULL),
(138, 2, 1, 1, '2025-09-05', '13:11:35', '13:20:03', '00:08:28', '', NULL),
(139, 2, 2, 10, '2025-09-05', '13:20:03', '13:25:04', '00:05:01', '', NULL),
(140, 2, 1, 1, '2025-09-05', '13:25:04', '15:00:14', '01:35:10', '', NULL),
(141, 2, 2, 11, '2025-09-05', '15:00:14', NULL, NULL, '', NULL),
(142, 9, 2, 11, '2025-09-05', '15:00:23', NULL, NULL, '', NULL),
(143, 8, 2, 11, '2025-09-05', '15:01:26', NULL, NULL, '', NULL),
(144, 2, 2, 13, '2025-09-06', '16:30:26', '20:30:19', '03:59:53', 'Tech Duty', NULL),
(145, 2, 2, 11, '2025-09-06', '20:30:19', NULL, NULL, '', NULL),
(146, 2, 2, 13, '2025-09-07', '13:33:59', '17:30:05', '03:56:06', 'Tech Duty', NULL),
(147, 2, 2, 11, '2025-09-07', '17:30:05', NULL, NULL, '', NULL),
(148, 8, 1, 1, '2025-09-08', '06:00:02', '11:21:15', '05:21:13', '', NULL),
(149, 2, 1, 1, '2025-09-08', '06:00:02', '11:30:20', '05:30:18', '', NULL),
(150, 9, 1, 1, '2025-09-08', '06:00:12', '11:15:19', '05:15:07', '', NULL),
(151, 4, 6, 33, '2025-09-08', '08:14:10', '08:59:52', '00:45:42', '', NULL),
(152, 4, 6, 32, '2025-09-08', '08:59:52', '11:34:15', '02:34:23', '', NULL),
(153, 9, 2, 10, '2025-09-08', '11:15:19', '12:30:34', '01:15:15', '', NULL),
(154, 8, 2, 10, '2025-09-08', '11:21:15', '12:48:41', '01:27:26', '', NULL),
(155, 2, 2, 10, '2025-09-08', '11:30:20', '13:00:04', '01:29:44', '', NULL),
(156, 4, 6, 33, '2025-09-08', '11:34:15', '17:03:28', '05:29:13', '', NULL),
(157, 9, 1, 1, '2025-09-08', '12:30:34', '13:54:12', '01:23:38', '', NULL),
(158, 8, 1, 1, '2025-09-08', '12:48:41', '15:00:43', '02:12:02', '', NULL),
(159, 2, 1, 1, '2025-09-08', '13:00:04', '15:00:02', '01:59:58', '', NULL),
(160, 9, 1, 1, '2025-09-08', '13:54:12', '15:00:13', '01:06:01', '', NULL),
(161, 2, 2, 10, '2025-09-08', '15:00:02', '16:30:28', '01:30:26', '', NULL),
(162, 9, 2, 11, '2025-09-08', '15:00:13', NULL, NULL, '', NULL),
(163, 8, 2, 11, '2025-09-08', '15:00:43', NULL, NULL, '', NULL),
(164, 2, 2, 13, '2025-09-08', '16:30:28', '19:30:20', '02:59:52', '', NULL),
(165, 4, 2, 11, '2025-09-08', '17:03:28', NULL, NULL, '', NULL),
(166, 2, 2, 11, '2025-09-08', '19:30:20', NULL, NULL, '', NULL),
(167, 9, 2, 10, '2025-09-09', '07:02:49', '07:10:47', '00:07:58', '', NULL),
(168, 9, 1, 1, '2025-09-09', '07:10:47', '10:55:15', '03:44:28', '', NULL),
(169, 4, 6, 33, '2025-09-09', '08:33:55', NULL, NULL, '', NULL),
(170, 9, 2, 10, '2025-09-09', '10:55:15', '12:20:24', '01:25:09', '', NULL),
(171, 8, 1, 1, '2025-09-09', '11:34:06', '11:34:17', '00:00:11', '', NULL),
(172, 8, 2, 10, '2025-09-09', '11:34:17', '13:08:13', '01:33:56', '', NULL),
(173, 9, 1, 1, '2025-09-09', '12:20:24', '15:00:06', '02:39:42', '', NULL),
(174, 6, 5, 28, '2025-09-09', '13:06:02', NULL, NULL, '', NULL),
(175, 8, 1, 1, '2025-09-09', '13:08:13', '15:00:27', '01:52:14', '', NULL),
(176, 9, 2, 11, '2025-09-09', '15:00:06', NULL, NULL, '', NULL),
(177, 8, 2, 11, '2025-09-09', '15:00:27', NULL, NULL, '', NULL),
(178, 2, 2, 13, '2025-09-09', '19:04:38', '22:04:09', '02:59:31', '', NULL),
(179, 2, 2, 11, '2025-09-09', '22:04:09', NULL, NULL, '', NULL),
(180, 2, 1, 1, '2025-09-10', '06:00:07', '11:00:05', '04:59:58', '', NULL),
(181, 9, 1, 1, '2025-09-10', '06:00:40', '10:43:47', '04:43:07', '', NULL),
(182, 8, 1, 1, '2025-09-10', '06:00:54', '10:44:40', '04:43:46', '', NULL),
(183, 6, 2, 11, '2025-09-10', '06:57:06', NULL, NULL, '', NULL),
(184, 4, 2, 11, '2025-09-10', '07:22:20', NULL, NULL, '', NULL),
(185, 9, 2, 10, '2025-09-10', '10:43:47', '12:00:25', '01:16:38', '', NULL),
(186, 8, 2, 10, '2025-09-10', '10:44:40', '11:53:28', '01:08:48', '', NULL),
(187, 2, 2, 10, '2025-09-10', '11:00:05', '12:01:18', '01:01:13', '', NULL),
(188, 8, 1, 1, '2025-09-10', '11:53:28', '12:01:24', '00:07:56', '', NULL),
(189, 9, 1, 3, '2025-09-10', '12:00:25', '12:04:56', '00:04:31', '', NULL),
(190, 2, 1, 3, '2025-09-10', '12:01:18', '12:04:54', '00:03:36', '', NULL),
(191, 8, 1, 3, '2025-09-10', '12:01:24', '12:04:56', '00:03:32', '', NULL),
(192, 2, 2, 10, '2025-09-10', '12:04:54', '12:33:18', '00:28:24', '', NULL),
(193, 8, 1, 1, '2025-09-10', '12:04:56', '13:25:02', '01:20:06', '', NULL),
(194, 9, 1, 1, '2025-09-10', '12:04:56', '12:41:23', '00:36:27', '', NULL),
(195, 2, 1, 1, '2025-09-10', '12:33:18', '15:00:09', '02:26:51', '', NULL),
(196, 9, 2, 10, '2025-09-10', '12:41:23', '12:49:48', '00:08:25', '', NULL),
(197, 9, 1, 1, '2025-09-10', '12:49:48', '15:00:12', '02:10:24', '', NULL),
(198, 8, 2, 10, '2025-09-10', '13:25:02', '13:45:58', '00:20:56', '', NULL),
(199, 8, 1, 1, '2025-09-10', '13:45:58', '15:00:35', '01:14:37', '', NULL),
(200, 2, 2, 11, '2025-09-10', '15:00:09', NULL, NULL, '', NULL),
(201, 9, 2, 11, '2025-09-10', '15:00:12', NULL, NULL, '', NULL),
(202, 8, 2, 11, '2025-09-10', '15:00:35', NULL, NULL, '', NULL),
(203, 8, 1, 1, '2025-09-11', '06:00:03', '11:51:21', '05:51:18', '', NULL),
(204, 2, 1, 1, '2025-09-11', '06:00:50', '11:32:06', '05:31:16', '', NULL),
(205, 9, 1, 1, '2025-09-11', '06:00:52', '11:00:42', '04:59:50', '', NULL),
(206, 2, 2, 10, '2025-09-11', '11:32:06', '13:02:33', '01:30:27', '', NULL),
(207, 8, 2, 10, '2025-09-11', '11:51:21', '13:16:45', '01:25:24', '', NULL),
(208, 9, 1, 1, '2025-09-11', '12:29:02', '15:00:43', '02:31:41', 'Away Break Missing', NULL),
(209, 2, 1, 1, '2025-09-11', '13:02:33', '15:00:36', '01:58:03', '', NULL),
(210, 8, 1, 1, '2025-09-11', '13:16:45', '15:01:02', '01:44:17', '', NULL),
(211, 2, 2, 10, '2025-09-11', '15:00:36', '20:10:23', '05:09:47', '', NULL),
(212, 9, 2, 11, '2025-09-11', '15:00:43', NULL, NULL, '', NULL),
(213, 8, 2, 11, '2025-09-11', '15:01:02', NULL, NULL, '', NULL),
(214, 2, 2, 13, '2025-09-11', '20:10:23', '23:10:47', '03:00:24', '', NULL),
(215, 2, 2, 11, '2025-09-11', '23:10:47', NULL, NULL, '', NULL),
(216, 9, 1, 1, '2025-09-12', '06:00:11', '11:02:57', '05:02:46', '', NULL),
(217, 2, 1, 1, '2025-09-12', '06:00:26', '07:05:08', '01:04:42', '', NULL),
(218, 8, 1, 1, '2025-09-12', '06:00:43', '12:12:26', '06:11:43', '', NULL),
(219, 2, 2, 10, '2025-09-12', '07:05:08', '07:20:35', '00:15:27', '', NULL),
(220, 2, 1, 1, '2025-09-12', '07:20:35', '11:03:42', '03:43:07', '', NULL),
(221, 9, 2, 10, '2025-09-12', '11:02:57', '12:28:06', '01:25:09', '', NULL),
(222, 2, 2, 10, '2025-09-12', '11:03:42', '12:18:03', '01:14:21', '', NULL),
(223, 2, 1, 1, '2025-09-12', '12:18:03', '15:00:30', '02:42:27', '', NULL),
(224, 8, 2, 10, '2025-09-12', '12:12:26', '13:40:41', '01:28:15', '', NULL),
(226, 9, 1, 1, '2025-09-12', '12:28:06', '15:01:20', '02:33:14', '', NULL),
(227, 8, 1, 1, '2025-09-12', '13:40:41', '15:00:19', '01:19:38', '', NULL),
(228, 8, 2, 11, '2025-09-12', '15:00:19', NULL, NULL, '', NULL),
(229, 2, 2, 11, '2025-09-12', '15:00:30', NULL, NULL, '', NULL),
(230, 9, 2, 11, '2025-09-12', '15:01:20', NULL, NULL, '', NULL),
(231, 2, 2, 13, '2025-09-13', '20:25:03', NULL, NULL, '', NULL),
(232, 2, 2, 11, '2025-09-13', '00:25:00', NULL, NULL, '', NULL),
(238, 2, 1, 1, '2025-09-17', '21:34:45', '21:45:48', '00:11:03', '', NULL),
(239, 2, 2, 10, '2025-09-17', '21:45:48', '21:49:33', '00:03:45', '', NULL),
(240, 2, 1, 1, '2025-09-17', '21:49:33', '22:00:00', '00:10:27', '', NULL),
(241, 2, 2, 11, '2025-09-17', '22:00:00', NULL, NULL, '', NULL),
(243, 2, 1, 1, '2025-09-19', '22:30:00', NULL, NULL, '', NULL),
(244, 2, 2, 11, '2025-09-19', '20:30:00', NULL, NULL, '', NULL),
(245, 2, 1, 1, '2025-09-19', '19:11:00', NULL, NULL, '', NULL),
(246, 23, 1, 1, '2025-09-30', '20:22:13', '20:42:28', '00:20:15', '', NULL),
(247, 23, 2, 11, '2025-09-30', '20:42:28', NULL, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `task_logs_archive`
--

CREATE TABLE `task_logs_archive` (
  `id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `work_mode_id` int(11) NOT NULL,
  `task_description_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `total_duration` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `volume_remark` decimal(10,2) DEFAULT NULL,
  `archived_month` date NOT NULL,
  `archived_at` datetime NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_logs_archive`
--

INSERT INTO `task_logs_archive` (`id`, `original_id`, `user_id`, `work_mode_id`, `task_description_id`, `date`, `start_time`, `end_time`, `total_duration`, `remarks`, `volume_remark`, `archived_month`, `archived_at`, `department_id`) VALUES
(1, 1, 2, 1, 1, '2025-07-23', '18:14:16', '18:26:22', '00:12:06', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(2, 2, 2, 2, 10, '2025-07-23', '18:26:22', '18:43:23', '00:17:01', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(3, 3, 2, 1, 1, '2025-07-23', '18:43:23', '18:53:31', '00:10:08', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(4, 4, 2, 2, 11, '2025-07-23', '18:53:31', NULL, NULL, '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(5, 5, 2, 1, 1, '2025-07-24', '19:18:00', '19:33:27', '00:15:27', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(6, 6, 2, 2, 10, '2025-07-24', '19:33:27', '20:28:55', '00:55:28', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(7, 7, 2, 1, 1, '2025-07-24', '20:28:55', '20:34:50', '00:05:55', '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(8, 8, 2, 2, 11, '2025-07-24', '20:34:50', NULL, NULL, '', NULL, '2025-07-01', '2025-08-19 12:00:31', NULL),
(9, 13, 2, 1, 1, '2025-08-17', '03:12:58', '03:43:38', '00:30:40', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(10, 14, 2, 2, 10, '2025-08-17', '03:43:38', '04:52:00', '01:08:22', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(11, 15, 2, 1, 1, '2025-08-17', '04:52:00', '12:52:46', '08:00:46', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(12, 16, 2, 2, 11, '2025-08-17', '12:52:46', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(13, 17, 2, 1, 1, '2025-08-20', '06:00:37', '11:00:10', '04:59:33', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(14, 18, 2, 2, 10, '2025-08-20', '11:00:10', '12:03:10', '01:03:00', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(15, 19, 2, 1, 3, '2025-08-20', '12:03:10', '12:07:42', '00:04:32', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(16, 20, 2, 2, 10, '2025-08-20', '12:07:42', '12:34:00', '00:26:18', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(17, 21, 2, 1, 1, '2025-08-20', '12:34:00', '15:00:15', '02:26:15', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(18, 22, 2, 2, 11, '2025-08-20', '15:00:15', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(19, 23, 2, 1, 1, '2025-08-21', '06:00:09', '07:12:03', '01:11:54', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(20, 24, 2, 2, 10, '2025-08-21', '07:12:03', '07:26:37', '00:14:34', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(21, 25, 2, 1, 1, '2025-08-21', '07:26:37', '11:44:32', '04:17:55', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(22, 26, 2, 2, 10, '2025-08-21', '11:44:32', '13:00:14', '01:15:42', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(23, 27, 2, 1, 1, '2025-08-21', '13:00:14', '15:00:02', '01:59:48', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(24, 28, 2, 2, 11, '2025-08-21', '15:00:02', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(25, 31, 4, 1, 1, '2025-08-26', '12:55:30', '17:00:00', '04:04:30', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(26, 32, 4, 2, 11, '2025-08-26', '17:00:00', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(27, 33, 2, 1, 1, '2025-08-27', '06:00:12', '12:00:54', '06:00:42', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(28, 34, 4, 1, 1, '2025-08-27', '07:50:59', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(29, 37, 2, 1, 3, '2025-08-27', '12:00:54', '12:08:40', '00:07:46', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(30, 38, 2, 2, 10, '2025-08-27', '12:08:40', '13:38:27', '01:29:47', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(31, 39, 2, 1, 1, '2025-08-27', '13:38:27', '15:00:11', '01:21:44', '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(32, 40, 2, 2, 11, '2025-08-27', '15:00:11', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(33, 41, 4, 1, 1, '2025-08-30', '19:50:40', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL),
(34, 42, 4, 1, 1, '2025-08-31', '08:28:05', NULL, NULL, '', NULL, '2025-08-01', '2025-08-31 14:37:17', NULL);

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
  `role` enum('admin','executive','user','hr','client','supervisor') NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `is_online`, `created_at`, `department_id`, `profile_image`, `status`) VALUES
(2, '', 'Mark Francis', 'Perez', 'De Guzman', 'markfrancis.deguzman@jetstar.com', '$2y$10$PnWWo2m8CuQfOkihmjtZWONayGmZpyVpiskTC5SSF0Kl1qRUzjHDe', 'executive', 1, '2025-07-14 05:20:27', 1, 'uploads/1759197980_8ra.jpg', 'active'),
(4, '', 'Kin', '', 'Nonog', 'kin@Resono.com.au', '$2y$10$70BoXxNECS1GJ/wieB97a.x1eOpc/koz9vpDcIgeBwd3TdPX/xMn2', 'executive', 0, '2025-08-20 05:40:20', NULL, '', 'active'),
(6, '', 'Carl Matthew', 'Baesa', 'Barot', 'cybersecurity@Resono.com.au', '$2y$10$4V6CPrsjAEuYcWvyPvySEOfrXCc9Sk/qArapHLVfJBqisSsEYDTmK', 'admin', 0, '2025-08-26 01:44:45', 6, '', 'active'),
(8, '', 'Aljay', '', 'Pedraza', 'Aljay.pedraza@jetstar.com', '$2y$10$UzmZH9W8lUBI2N1OZgGaYegvJZGtHTKXeLPgMdDTXiYX8Ymwo9HfK', 'user', 0, '2025-08-30 12:24:33', 1, '', 'active'),
(9, '', 'John Dave', '', 'Jarabelo', 'john.jarabelo@jetstar.com', '$2y$10$8NmWT5lKF/loMfOFGJEiT.LJBGDHpX122WhIPXNnuCJ6dfBj96y8m', 'user', 0, '2025-08-30 19:30:28', 1, '', 'active'),
(10, '', 'Miss', '', 'Mabz', 'resonohr@gmail.com', '$2y$10$6LMK1aTevqtEt41aTi6gweHINe9IAkLosVPU8eMDHMeuZJw2XJgEy', 'hr', 0, '2025-08-30 19:34:45', NULL, NULL, 'active'),
(12, '', 'Client', '', 'Local', 'clientaccount@gmail.com', '$2y$10$JHnJNRvczt3Yt/9z/1h8GeX2/jV.bA270a4mnDvoaxV2UeRGRd4xC', 'client', 0, '2025-09-14 06:04:55', NULL, 'uploads/1759200593_tesda_logo.png', 'active'),
(14, '', 'Exe', '', 'Account', 'executiveaccount@gmail.com', '$2y$10$VpWSb1cSuSwWJqrIh0b56OF9Vt0zvFTS87Lsj9A7Q1BbzN3RoJFrC', 'executive', 1, '2025-09-17 13:42:37', NULL, '', 'active'),
(22, '', 'Sample', '', 'Multi', 'testuser@gmail.com', '$2y$10$L6Lb/rDg3QJvX/yUns7vweMIimRsM8DskwcctPICub0JRxb2ujaQy', 'supervisor', 0, '2025-09-27 12:00:59', NULL, '/assets/default-avatar.jpg', 'active'),
(23, '', 'User', '', 'Local', 'userlocal@gmail.com', '$2y$10$0OR5SSh1DXqmtn9NmVA4PuyUaC.l7jPKkeBkUW0w3qw1nCWWPIHwq', 'user', 0, '2025-09-30 12:17:22', NULL, '/assets/default-avatar.jpg', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_departments`
--

CREATE TABLE `user_departments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_departments`
--

INSERT INTO `user_departments` (`id`, `user_id`, `department_id`, `is_primary`) VALUES
(29, 8, 1, 1),
(30, 9, 1, 1),
(42, 6, 6, 1),
(61, 2, 1, 1),
(70, 23, 2, 1),
(71, 22, 2, 1),
(72, 22, 1, 0),
(73, 12, 2, 1),
(74, 12, 3, 0);

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
(4, 'Ancillary'),
(5, 'Technology & Data'),
(6, 'Management');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_work_modes`
--
ALTER TABLE `department_work_modes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_dept_workmode` (`department_id`,`work_mode_id`),
  ADD KEY `work_mode_id` (`work_mode_id`);

--
-- Indexes for table `dtr_amendments`
--
ALTER TABLE `dtr_amendments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request_per_recipient` (`request_uid`,`recipient_id`);

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
-- Indexes for table `task_logs_archive`
--
ALTER TABLE `task_logs_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_archived_month` (`user_id`,`archived_month`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_department` (`department_id`);

--
-- Indexes for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `department_work_modes`
--
ALTER TABLE `department_work_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `dtr_amendments`
--
ALTER TABLE `dtr_amendments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `task_descriptions`
--
ALTER TABLE `task_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `task_logs`
--
ALTER TABLE `task_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=248;

--
-- AUTO_INCREMENT for table `task_logs_archive`
--
ALTER TABLE `task_logs_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_departments`
--
ALTER TABLE `user_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `work_modes`
--
ALTER TABLE `work_modes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_work_modes`
--
ALTER TABLE `department_work_modes`
  ADD CONSTRAINT `department_work_modes_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_work_modes_ibfk_2` FOREIGN KEY (`work_mode_id`) REFERENCES `work_modes` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `user_departments`
--
ALTER TABLE `user_departments`
  ADD CONSTRAINT `user_departments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
