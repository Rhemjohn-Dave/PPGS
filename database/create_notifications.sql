-- Create Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                     -- ID of the user receiving the notification
    message TEXT NOT NULL,                    -- Notification content
    link VARCHAR(255) DEFAULT NULL,           -- Optional link related to the notification (e.g., to the task)
    is_read BOOLEAN DEFAULT FALSE,            -- 0 for unread, 1 for read
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When the notification was created
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index for faster notification lookup per user
CREATE INDEX IF NOT EXISTS idx_notification_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_read_status ON notifications(user_id, is_read); 