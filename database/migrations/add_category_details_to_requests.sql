-- Add columns for printing details to task_requests table
ALTER TABLE task_requests ADD COLUMN num_copies INT AFTER category;
ALTER TABLE task_requests ADD COLUMN paper_size VARCHAR(50) AFTER num_copies;
ALTER TABLE task_requests ADD COLUMN paper_type VARCHAR(50) AFTER paper_size;

-- Add columns for repair details to task_requests table
ALTER TABLE task_requests ADD COLUMN equipment_name VARCHAR(255) AFTER paper_type;
ALTER TABLE task_requests ADD COLUMN problem_description TEXT AFTER equipment_name; 