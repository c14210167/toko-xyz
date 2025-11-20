-- Database updates for Orders Management and Chat features

-- Update messages table to support staff-customer chat
ALTER TABLE messages
ADD COLUMN IF NOT EXISTS receiver_id INT NULL AFTER sender_id,
ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0 AFTER message,
ADD INDEX idx_receiver (receiver_id),
ADD INDEX idx_order_sender (order_id, sender_id),
ADD INDEX idx_is_read (is_read);

-- Create order_status_history table to track status changes
CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_changed_by (changed_by),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Add ready_pickup status if not exists (update existing ENUM if needed)
-- Note: This is a suggestion - you may need to adjust based on your current table structure
-- ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'in_progress', 'waiting_parts', 'ready_pickup', 'completed', 'cancelled') DEFAULT 'pending';

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_orders_updated ON orders(updated_at);
CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_location ON orders(location_id);

-- Optional: Create expenses table if it doesn't exist (referenced in dashboard)
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    location_id INT,
    category VARCHAR(100),
    description TEXT,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location (location_id),
    INDEX idx_expense_date (expense_date),
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);
