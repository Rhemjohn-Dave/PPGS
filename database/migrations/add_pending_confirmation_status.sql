-- Add pending_confirmation status to tasks table
ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'rejected', 'pending_confirmation') DEFAULT 'pending'; 