-- Add category column to tasks table if it doesn't exist
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS category VARCHAR(50) AFTER priority;

-- Add category column to task_requests table if it doesn't exist
ALTER TABLE task_requests ADD COLUMN IF NOT EXISTS category VARCHAR(50) AFTER reason;

-- Create index on category column
CREATE INDEX IF NOT EXISTS idx_task_category ON tasks(category);
CREATE INDEX IF NOT EXISTS idx_task_request_category ON task_requests(category);

-- Update task category from corresponding request category
UPDATE tasks t
JOIN task_requests tr ON t.id = tr.task_id
SET t.category = tr.category
WHERE t.category IS NULL AND tr.category IS NOT NULL; 