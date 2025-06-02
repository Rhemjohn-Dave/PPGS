-- First, update any existing tasks to use the priority from their request
UPDATE tasks t
JOIN task_requests tr ON t.request_id = tr.id
SET t.priority = tr.priority
WHERE t.priority IS NULL OR t.priority = 'medium';

-- Now we can safely drop the priority column from task_requests
ALTER TABLE task_requests DROP COLUMN priority; 