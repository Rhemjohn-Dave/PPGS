-- Add head_id column to departments table
ALTER TABLE departments
ADD COLUMN head_id INT,
ADD FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL;

-- Update schema.sql to include head_id in departments table
-- Note: This is just a comment for future reference, the actual schema.sql should be updated manually
/*
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    head_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);
*/ 