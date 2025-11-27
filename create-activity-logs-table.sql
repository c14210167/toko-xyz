-- Create Activity Logs Table
-- This table tracks all activities performed by employees

USE xyz_service;

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- 'login', 'logout', 'order_update', 'permission_change', 'role_update', 'location_add', etc.
    action_description TEXT NOT NULL,
    related_entity_type VARCHAR(50) NULL, -- 'order', 'role', 'permission', 'location', 'user', etc.
    related_entity_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_entity (related_entity_type, related_entity_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show table structure
DESCRIBE activity_logs;

SELECT 'Activity logs table created successfully!' AS status;
