-- TUP PPGS Database Schema
-- This file contains all necessary database definitions, updates, and initial data

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS tup_ppgs_tasks;
USE tup_ppgs_tasks;

-- =============================================
-- Table Definitions
-- =============================================

-- Create departments table first (without the head_id foreign key initially)
CREATE TABLE IF NOT EXISTS departments (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    head_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create users table (referencing departments)
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('user', 'program head', 'adaa', 'admin', 'staff') NOT NULL,
    department_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email),
    KEY department_id (department_id),
    CONSTRAINT users_ibfk_1 FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create task_requests table
CREATE TABLE IF NOT EXISTS task_requests (
    id INT NOT NULL AUTO_INCREMENT,
    requester_id INT NOT NULL,
    department_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    reason TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    num_copies INT DEFAULT NULL,
    paper_size VARCHAR(50) DEFAULT NULL,
    paper_type VARCHAR(50) DEFAULT NULL,
    equipment_name VARCHAR(255) DEFAULT NULL,
    problem_description TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    program_head_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    adaa_approval ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    due_date DATE DEFAULT NULL,
    PRIMARY KEY (id),
    KEY requester_id (requester_id),
    KEY department_id (department_id),
    KEY idx_task_requests_status (status),
    KEY idx_task_requests_approvals (program_head_approval, adaa_approval),
    CONSTRAINT task_requests_ibfk_1 FOREIGN KEY (requester_id) REFERENCES users (id),
    CONSTRAINT task_requests_ibfk_2 FOREIGN KEY (department_id) REFERENCES departments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT NOT NULL AUTO_INCREMENT,
    request_id INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'rejected', 'pending_confirmation', 'postponed') DEFAULT 'pending',
    postponement_reasons TEXT DEFAULT NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    due_date DATE DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY request_id (request_id),
    KEY assigned_to (assigned_to),
    KEY idx_tasks_status (status),
    KEY idx_tasks_due_date (due_date),
    KEY idx_tasks_priority (priority),
    CONSTRAINT tasks_ibfk_1 FOREIGN KEY (request_id) REFERENCES task_requests (id),
    CONSTRAINT tasks_ibfk_2 FOREIGN KEY (assigned_to) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create task_comments table
CREATE TABLE IF NOT EXISTS task_comments (
    id INT NOT NULL AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY user_id (user_id),
    CONSTRAINT task_comments_ibfk_1 FOREIGN KEY (task_id) REFERENCES tasks (id),
    CONSTRAINT task_comments_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create task_attachments table
CREATE TABLE IF NOT EXISTS task_attachments (
    id INT NOT NULL AUTO_INCREMENT,
    task_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    uploaded_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY uploaded_by (uploaded_by),
    CONSTRAINT task_attachments_ibfk_1 FOREIGN KEY (task_id) REFERENCES tasks (id),
    CONSTRAINT task_attachments_ibfk_2 FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id),
    KEY idx_notification_read_status (user_id, is_read),
    CONSTRAINT notifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- Initial Data
-- =============================================

-- Insert default departments (without head_id initially)
INSERT INTO departments (id, name, description, created_at, updated_at) VALUES
(1, 'ECE', 'Electronics Engineering', '2025-04-22 14:57:55', '2025-04-22 15:21:37'),
(2, 'ME', 'Mechanical Engineering', '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(3, 'EE', 'Electrical Engineering', '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(4, 'CpE', 'Computer Engineering', '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(5, 'MxE', 'Mechatronics Engineering', '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(6, 'ECT', 'Electronics Technology', '2025-04-22 14:57:55', '2025-04-22 14:57:55');

-- Insert default users
INSERT INTO users (id, username, email, password, full_name, role, department_id, created_at, updated_at) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(2, 'user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rhemjohn Dave', 'user', 1, '2025-04-22 14:57:55', '2025-05-28 13:31:08'),
(3, 'staff', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Regular User', 'staff', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(4, 'adaa', 'adaa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Patrick Delumpa', 'adaa', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55'),
(5, 'programhead', 'programhead@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Natz Deldo', 'program head', 1, '2025-04-22 14:57:55', '2025-04-22 15:47:46'),
(6, 'staff1', 'staff1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff 1', 'staff', NULL, '2025-04-22 14:57:55', '2025-04-22 14:57:55');

-- Update departments with head_id after users exist
UPDATE departments SET head_id = 5 WHERE id = 1;

-- Add the foreign key constraint for head_id after data is inserted
ALTER TABLE departments 
ADD CONSTRAINT departments_ibfk_1 
FOREIGN KEY (head_id) REFERENCES users (id) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;




