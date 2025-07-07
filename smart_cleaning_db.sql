-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 04:46 AM
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
-- Database: `smart_cleaning_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `cleaner_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','in-progress','completed','missed') DEFAULT 'pending',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `cleaner_id`, `route_id`, `assigned_by`, `assigned_at`, `status`, `start_time`, `end_time`) VALUES
(9, 3, 7, 1, '2025-07-03 19:55:44', 'missed', '2025-07-03 22:10:44', '2025-07-03 22:40:44'),
(11, 4, 10, 1, '2025-07-03 19:55:44', 'completed', '2025-07-03 22:10:44', '2025-07-03 22:55:44'),
(13, 5, 7, 1, '2025-07-06 06:30:00', 'in-progress', '2025-07-06 08:45:00', '2025-07-06 09:15:00'),
(14, 6, 8, 2, '2025-07-04 07:00:00', 'completed', '2025-07-04 09:15:00', '2025-07-04 09:45:00'),
(15, 7, 6, 1, '2025-07-06 05:00:00', 'pending', '2025-07-06 07:15:00', '2025-07-06 07:45:00'),
(16, 8, 10, 2, '2025-07-06 05:30:00', 'missed', '2025-07-06 07:45:00', '2025-07-06 08:15:00'),
(17, 3, 7, 1, '2025-07-03 19:55:44', 'missed', '2025-07-03 22:10:44', '2025-07-03 22:40:44'),
(19, 4, 10, 1, '2025-07-03 19:55:44', 'completed', '2025-07-03 22:10:44', '2025-07-03 22:55:44'),
(21, 5, 7, 1, '2025-07-06 06:30:00', 'in-progress', '2025-07-06 08:45:00', '2025-07-06 09:15:00'),
(22, 6, 8, 2, '2025-07-04 07:00:00', 'completed', '2025-07-04 09:15:00', '2025-07-04 09:45:00'),
(23, 7, 6, 1, '2025-07-06 05:00:00', 'pending', '2025-07-06 07:15:00', '2025-07-06 07:45:00'),
(24, 8, 10, 2, '2025-07-06 05:30:00', 'missed', '2025-07-06 07:45:00', '2025-07-06 08:15:00'),
(26, 13, 8, 1, '2025-07-07 01:37:21', 'pending', NULL, NULL),
(27, 1, 17, 1, '2025-07-07 01:44:18', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cleaners`
--

CREATE TABLE `cleaners` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `status` enum('available','on-duty','off-duty') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaners`
--

INSERT INTO `cleaners` (`id`, `user_id`, `photo`, `shift`, `status`) VALUES
(1, 6, 'cleaner.jpg', '08:00 - 17:00', ''),
(2, 3, 'cleaner1.jpg', '08:00 - 17:00', ''),
(3, 4, 'cleaner2.jpg', '08:00 - 17:00', ''),
(4, 7, 'cleaner3.jpg', '08:00 - 17:00', 'available'),
(5, 8, 'cleaner4.jpg', '08:00 - 17:00', 'available'),
(6, 9, 'cleaner5.jpg', '08:00 - 17:00', 'available'),
(7, 10, 'cleaner6.jpg', '08:00 - 17:00', 'available'),
(8, 11, 'cleaner7.jpg', '08:00 - 17:00', 'available'),
(9, 7, 'cleaner3.jpg', '08:00 - 17:00', 'available'),
(10, 8, 'cleaner4.jpg', '08:00 - 17:00', 'available'),
(11, 9, 'cleaner5.jpg', '08:00 - 17:00', 'available'),
(12, 10, 'cleaner6.jpg', '08:00 - 17:00', 'available'),
(13, 11, 'cleaner7.jpg', '08:00 - 17:00', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_logs`
--

CREATE TABLE `cleaning_logs` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `status` enum('started','in-progress','completed','missed') NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaning_logs`
--

INSERT INTO `cleaning_logs` (`id`, `assignment_id`, `status`, `notes`, `logged_at`) VALUES
(17, 9, 'missed', 'Log for assignment 9', '2025-07-06 13:55:44'),
(19, 11, 'started', 'Log for assignment 11', '2025-07-06 15:55:44'),
(21, 13, 'completed', 'Log for assignment 13', '2025-07-06 07:45:00'),
(22, 14, '', 'Log for assignment 14', '2025-07-04 06:15:00'),
(23, 15, 'missed', 'Log for assignment 15', '2025-07-06 08:00:00'),
(24, 16, 'completed', 'Log for assignment 16', '2025-07-06 11:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('assignment','alert','system','weather') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 4, 'Notification 1', 'Cleaning assignment scheduled for tomorrow at 8:00 AM.', 'system', 0, '2025-07-06 13:55:44'),
(2, 4, 'Notification 2', 'Heavy rains expected in your area today. Take necessary precautions while on route.', 'weather', 1, '2025-07-05 12:55:44'),
(3, 4, 'Notification 3', 'The cleaning schedules have been updated. Please check your dashboard for the latest information.', 'alert', 1, '2025-07-06 13:55:44'),
(4, 3, 'Notification 4', 'Reminder: Please complete your current cleaning assignment before the end of the day.', '', 0, '2025-07-06 06:00:00'),
(5, 2, 'Notification 5', 'Severe wind warnings issued for your area. Stay safe and report any delays promptly.', 'weather', 0, '2025-07-06 05:30:00'),
(6, 6, 'New Assignment', 'You have been assigned to clean: Rev Michael Scott Street', 'assignment', 0, '2025-07-07 01:10:57'),
(7, 2, 'Task Completed', 'Route Hosea Kutako Dr has been completed', 'system', 0, '2025-07-07 01:30:14'),
(8, 11, 'New Assignment', 'You have been assigned to clean: Nelson Mandela Ave', 'assignment', 0, '2025-07-07 01:37:21'),
(9, 6, 'New Assignment', 'You have been assigned to clean: John Meinert Street', 'assignment', 0, '2025-07-07 01:44:18');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`coordinates`)),
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `estimated_time` int(11) DEFAULT NULL COMMENT 'Estimated cleaning time in minutes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `coordinates`, `priority`, `estimated_time`, `created_at`) VALUES
(1, 'Mandume Ndemufayo Ave', '[[-22.54916962529988, 17.087904012443964], [-22.548169625299877, 17.088904012443965]]', 'high', 45, '2025-07-06 22:20:24'),
(2, 'Nelson Mandela Ave', '[[-22.541289560665454, 17.083985141518983], [-22.540289560665453, 17.084985141518985]]', 'high', 90, '2025-07-06 22:20:24'),
(4, 'Lüderitz Street', '[[-22.560244175707926, 17.060874355523016], [-22.559244175707924, 17.061874355523017]]', 'high', 90, '2025-07-06 22:20:24'),
(6, 'Rev Michael Scott Street', '[[-22.55575340230338, 17.063836050363385], [-22.55475340230338, 17.064836050363386]]', 'high', 50, '2025-07-06 22:20:24'),
(7, 'Mandume Ndemufayo Ave', '[[-22.54916962529988, 17.087904012443964], [-22.548169625299877, 17.088904012443965]]', 'high', 45, '2025-07-06 22:31:40'),
(8, 'Nelson Mandela Ave', '[[-22.541289560665454, 17.083985141518983], [-22.540289560665453, 17.084985141518985]]', 'high', 90, '2025-07-06 22:31:40'),
(10, 'Lüderitz Street', '[[-22.560244175707926, 17.060874355523016], [-22.559244175707924, 17.061874355523017]]', 'high', 90, '2025-07-06 22:31:40'),
(11, 'John Meinert Street', '[[-22.56699616645711, 17.082852918277002], [-22.565996166457108, 17.083852918277003]]', 'low', 45, '2025-07-06 22:31:40'),
(13, 'Mandume Ndemufayo Ave', '[[-22.54916962529988, 17.087904012443964], [-22.548169625299877, 17.088904012443965]]', 'high', 45, '2025-07-06 22:33:51'),
(14, 'Nelson Mandela Ave', '[[-22.541289560665454, 17.083985141518983], [-22.540289560665453, 17.084985141518985]]', 'high', 90, '2025-07-06 22:33:51'),
(15, 'Hosea Kutako Dr', '[[-22.567766363241414, 17.099455245588416], [-22.566766363241413, 17.100455245588417]]', 'medium', 60, '2025-07-06 22:33:51'),
(16, 'Lüderitz Street', '[[-22.560244175707926, 17.060874355523016], [-22.559244175707924, 17.061874355523017]]', 'high', 90, '2025-07-06 22:33:51'),
(17, 'John Meinert Street', '[[-22.56699616645711, 17.082852918277002], [-22.565996166457108, 17.083852918277003]]', 'low', 45, '2025-07-06 22:33:51'),
(19, 'Ocean View West', '[[-22.5609,17.0658],[-22.561,17.0659]]', 'low', 35, '2025-07-07 02:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('supervisor','cleaner') NOT NULL,
  `registration_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `registration_code`, `created_at`) VALUES
(1, 'supervisor1', 'supervisor1@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', 'sh123', '2025-07-06 14:29:46'),
(2, 'supervisor2', 'supervisor2@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', 'pw456', '2025-07-06 14:29:46'),
(3, 'cleaner1', 'cleaner1@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'er143', '2025-07-06 15:28:37'),
(4, 'cleaner2', 'cleaner2@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'yu546', '2025-07-06 15:28:37'),
(6, 'cleaner', 'cleaner@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', NULL, '2025-07-06 21:44:12'),
(7, 'cleaner3', 'cleaner3@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'cln789', '2025-07-06 22:10:16'),
(8, 'cleaner4', 'cleaner4@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'cln890', '2025-07-06 22:10:16'),
(9, 'cleaner5', 'cleaner5@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'cln901', '2025-07-06 22:10:16'),
(10, 'cleaner6', 'cleaner6@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'cln012', '2025-07-06 22:10:16'),
(11, 'cleaner7', 'cleaner7@windhoek.na', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner', 'cln123', '2025-07-06 22:10:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cleaner_id` (`cleaner_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `cleaners`
--
ALTER TABLE `cleaners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cleaning_logs`
--
ALTER TABLE `cleaning_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `registration_code` (`registration_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `cleaners`
--
ALTER TABLE `cleaners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cleaning_logs`
--
ALTER TABLE `cleaning_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`cleaner_id`) REFERENCES `cleaners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cleaners`
--
ALTER TABLE `cleaners`
  ADD CONSTRAINT `cleaners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cleaning_logs`
--
ALTER TABLE `cleaning_logs`
  ADD CONSTRAINT `cleaning_logs_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
