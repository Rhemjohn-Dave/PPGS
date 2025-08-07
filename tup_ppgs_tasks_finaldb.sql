-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2025 at 12:40 PM
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
-- Database: `tup_ppgs_tasks`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_id`, `created_at`, `updated_at`) VALUES
(1, 'ECE', 'Electronics Engineering', 5, '2025-04-22 14:57:55', '2025-07-11 06:34:28'),
(2, 'ME', 'Mechanical Engineering', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(3, 'EE', 'Electrical Engineering', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(4, 'CpE', 'Computer Engineering', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(5, 'MxE', 'Mechatronics Engineering', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(6, 'ECT', 'Electronics Technology', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(7, 'ElexTech', 'ElexTech', 5, '2025-07-15 10:03:30', '2025-07-15 10:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 5, 'New task request: \"dasd\" submitted by user.', 'task_approvals.php', 1, '2025-07-15 05:27:17'),
(2, 2, 'Your task request has been approved by programhead', 'task_requests.php', 1, '2025-07-15 05:27:34'),
(3, 4, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-15 05:27:34'),
(4, 8, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-15 05:27:34'),
(5, 5, 'New task request: \"test\" submitted by user.', 'task_approvals.php', 1, '2025-07-16 10:16:54'),
(6, 2, 'Your task request has been approved by programhead', 'task_requests.php', 0, '2025-07-16 10:17:12'),
(7, 4, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-16 10:17:12'),
(8, 8, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 1, '2025-07-16 10:17:12'),
(9, 9, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-16 10:17:12'),
(10, 2, 'Your task request has been approved by cfaciolan', 'task_requests.php', 0, '2025-07-16 10:17:25'),
(11, 1, 'Task request from Rhemjohn Dave has been approved and may need resource allocation', 'task_approvals.php', 0, '2025-07-16 10:17:25'),
(12, 2, 'Your task request has been approved by cfaciolan', 'task_requests.php', 0, '2025-07-16 10:17:26'),
(13, 1, 'Task request from Rhemjohn Dave has been approved and may need resource allocation', 'task_approvals.php', 1, '2025-07-16 10:17:26'),
(14, 3, 'You have been assigned a new task: test', 'tasks.php?view_task=1', 0, '2025-07-16 10:22:27'),
(15, 2, 'Your task \'test\' has been assigned to a staff member.', 'tasks.php?view_request=3', 0, '2025-07-16 10:22:27'),
(16, 3, 'You have been assigned a new task: dasd', 'tasks.php?view_task=2', 0, '2025-07-16 11:03:48'),
(17, 2, 'Your task \'dasd\' has been assigned to a staff member.', 'tasks.php?view_request=2', 0, '2025-07-16 11:03:48'),
(18, 2, 'Your task \'dasd\' has been started by staff', 'view_task.php?id=2', 0, '2025-07-16 11:04:14'),
(19, 2, 'Your task \'dasd\' has been marked as finished by staff', 'view_task.php?id=2', 1, '2025-07-16 11:04:17'),
(20, 3, 'Your task \'dasd\' has been confirmed as completed with the following notes: dasdasd', 'view_task.php?id=2', 0, '2025-07-16 11:04:43'),
(21, 5, 'New task request: \"test\" submitted by user.', 'task_approvals.php', 1, '2025-07-17 10:54:02'),
(22, 2, 'Your task request has been approved by programhead', 'task_requests.php', 0, '2025-07-17 10:54:37'),
(23, 4, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 10:54:37'),
(24, 8, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 10:54:37'),
(25, 9, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 10:54:37'),
(26, 5, 'New task request: \"test12\" submitted by user.', 'task_approvals.php', 1, '2025-07-17 11:00:30'),
(27, 2, 'Your task request has been approved by programhead', 'task_requests.php', 0, '2025-07-17 11:27:41'),
(28, 4, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 11:27:41'),
(29, 8, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 11:27:41'),
(30, 9, 'Task request from Rhemjohn Dave has been approved by programhead', 'task_approvals.php', 0, '2025-07-17 11:27:41'),
(31, 2, 'Your task request has been rejected by adaa', 'task_requests.php', 0, '2025-07-17 11:27:53'),
(32, 2, 'Your task \'test\' has been started by staff', 'view_task.php?id=1', 0, '2025-07-19 10:55:29'),
(33, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 10:56:07'),
(34, 3, 'Your task \'test\' was sent back by the requestor for further work.', 'view_task.php?id=1', 0, '2025-07-19 11:30:23'),
(40, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 11:33:20'),
(41, 3, 'Your task \'test\' was sent back by the requestor for further work.', 'view_task.php?id=1', 1, '2025-07-19 11:33:41'),
(43, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 11:35:06'),
(44, 3, 'Your task \'test\' was sent back by the requestor for further work.', 'view_task.php?id=1', 0, '2025-07-19 11:39:21'),
(45, 2, 'Your task \'test\' has been started by staff', 'view_task.php?id=1', 0, '2025-07-19 11:46:24'),
(46, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 11:46:36'),
(47, 3, 'Your task \'test\' has been confirmed as completed', 'view_task.php?id=1', 0, '2025-07-19 11:46:47'),
(48, 2, 'Your task \'dasd\' has been started by staff', 'view_task.php?id=2', 0, '2025-07-19 11:49:26'),
(49, 2, 'Your task \'test\' has been started by staff', 'view_task.php?id=1', 0, '2025-07-19 11:49:57'),
(50, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 11:50:40'),
(51, 2, 'Your task \'dasd\' has been marked as finished by staff', 'view_task.php?id=2', 0, '2025-07-19 11:50:43'),
(52, 3, 'Your task \'test\' was sent back by the requestor for further work.', 'view_task.php?id=1', 0, '2025-07-19 11:50:49'),
(54, 3, 'You have a new follow-up from the requestor on a postponed task.', 'view_task.php?id=2', 1, '2025-07-19 12:07:13'),
(56, 2, 'Your task \'test\' has been started by staff', 'view_task.php?id=1', 0, '2025-07-19 12:26:24'),
(57, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 12:26:39'),
(58, 3, 'Your task \'test\' has been confirmed as completed', 'view_task.php?id=1', 0, '2025-07-19 12:26:47'),
(59, 2, 'Your task \'test\' has been marked as finished by staff', 'view_task.php?id=1', 0, '2025-07-19 12:27:28'),
(60, 3, 'Your task \'test\' has been confirmed as completed with the following notes: good', 'view_task.php?id=1', 0, '2025-07-19 12:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','rejected','pending_confirmation','postponed') DEFAULT 'pending',
  `postponement_reasons` text DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `request_id`, `assigned_to`, `status`, `postponement_reasons`, `priority`, `due_date`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 'completed', NULL, 'medium', '2025-07-16', '2025-07-19 18:28:50', '2025-07-16 16:22:27', '2025-07-19 18:28:50'),
(2, 2, 3, 'in_progress', 'waiting_for_resources', 'medium', '2025-07-16', '2025-07-16 17:04:43', '2025-07-16 17:03:48', '2025-07-19 18:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_completion_notes`
--

CREATE TABLE `task_completion_notes` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text NOT NULL,
  `note_type` enum('completion','rejection','postponement','followup') NOT NULL DEFAULT 'completion',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_completion_notes`
--

INSERT INTO `task_completion_notes` (`id`, `task_id`, `user_id`, `notes`, `note_type`, `created_at`) VALUES
(1, 2, 2, 'dasdasd', 'completion', '2025-07-16 17:04:43'),
(2, 1, 2, 'dsdsds', 'rejection', '2025-07-19 17:50:49'),
(3, 2, 2, 't?', 'followup', '2025-07-19 18:07:13'),
(4, 1, 2, 'good', 'completion', '2025-07-19 18:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `task_requests`
--

CREATE TABLE `task_requests` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `reason` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `num_copies` int(11) DEFAULT NULL,
  `paper_size` varchar(50) DEFAULT NULL,
  `paper_type` varchar(50) DEFAULT NULL,
  `equipment_name` varchar(255) DEFAULT NULL,
  `problem_description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `program_head_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `adaa_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_requests`
--

INSERT INTO `task_requests` (`id`, `requester_id`, `department_id`, `title`, `description`, `reason`, `category`, `num_copies`, `paper_size`, `paper_type`, `equipment_name`, `problem_description`, `status`, `program_head_approval`, `adaa_approval`, `created_at`, `updated_at`, `due_date`) VALUES
(2, 2, 1, 'dasd', '', 'dasd', 'maintenance', NULL, NULL, NULL, NULL, NULL, '', 'approved', 'approved', '2025-07-15 11:27:17', '2025-07-16 17:03:48', '2025-07-16'),
(3, 2, 1, 'test', '', 'dsad', 'repairs', NULL, NULL, NULL, 'dasd', NULL, 'pending', 'approved', 'approved', '2025-07-16 16:16:54', '2025-07-19 17:48:49', '2025-07-16'),
(4, 2, 1, 'test', '', 'test', 'repairs', NULL, NULL, NULL, 'test', NULL, 'pending', 'approved', 'pending', '2025-07-17 16:54:02', '2025-07-17 16:54:37', '2025-07-18'),
(5, 2, 1, 'test12', '', 'test12', 'repairs', NULL, NULL, NULL, 'test12', NULL, 'rejected', 'approved', 'rejected', '2025-07-17 17:00:30', '2025-07-17 17:27:53', '2025-07-18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('user','program head','adaa','admin','staff') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(2, 'user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rhemjohn Dave', 'user', 1, '2025-04-22 14:57:55', '2025-05-28 13:31:08'),
(3, 'staff', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Regular User', 'staff', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(4, 'adaa', 'adaa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Patrick Delumpa', 'adaa', NULL, '2025-04-22 14:57:55', '2025-07-11 14:34:47'),
(5, 'programhead', 'programhead@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Natz Deldo', 'program head', 1, '2025-04-22 14:57:55', '2025-04-22 15:47:46'),
(7, 'test', 'test@gmail.com', '$2y$10$C28PnN7Wxxr9rImpNVduTO/MQfbcb5f9i7H/jO.LDZ4ew8N3ouupe', 'test', 'user', 3, '2025-07-15 10:40:46', '2025-07-15 10:40:46'),
(8, 'cfaciolan', 'cfaciolan@gmail.com', '$2y$10$Wwzb/Ga7fI8fmHxPQvRmdeI8F07nL5VLA7lp8HBBcAckuVWRDM7tO', 'Faciolan', 'adaa', NULL, '2025-07-15 11:05:20', '2025-07-15 11:27:54'),
(9, 'xdarlord', 'rdpitong@gmail.com', '$2y$10$5qFjiLOnZKSwHd3kEMH2GOzEAoLFwhPxj0iw6fyOPE4lxuD823l8G', '', 'adaa', NULL, '2025-07-16 09:38:39', '2025-07-16 09:38:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `departments_ibfk_1` (`head_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notification_read_status` (`user_id`,`is_read`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_due_date` (`due_date`),
  ADD KEY `idx_tasks_priority` (`priority`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_completion_notes`
--
ALTER TABLE `task_completion_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_requests`
--
ALTER TABLE `task_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_task_requests_status` (`status`),
  ADD KEY `idx_task_requests_approvals` (`program_head_approval`,`adaa_approval`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_completion_notes`
--
ALTER TABLE `task_completion_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_requests`
--
ALTER TABLE `task_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `task_requests` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD CONSTRAINT `task_attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `task_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_completion_notes`
--
ALTER TABLE `task_completion_notes`
  ADD CONSTRAINT `task_completion_notes_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_completion_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_requests`
--
ALTER TABLE `task_requests`
  ADD CONSTRAINT `task_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `task_requests_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
