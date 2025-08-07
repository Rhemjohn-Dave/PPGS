-- Add priority field to tasks table
ALTER TABLE tasks ADD COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium' AFTER status;
ALTER TABLE tasks ADD INDEX idx_priority (priority);

-- Update existing tasks to have medium priority
UPDATE tasks SET priority = 'medium' WHERE priority IS NULL;

USE tup_ppgs_tasks;

-- Modify the role ENUM to include 'staff'
ALTER TABLE users MODIFY COLUMN role ENUM('user', 'program head', 'adaa', 'admin', 'staff') NOT NULL; 