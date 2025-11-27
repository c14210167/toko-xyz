-- ============================================
-- FIX: Create Missing Inventory Tables
-- Run this in phpMyAdmin to create inventory tables
-- ============================================

USE xyz_service;

-- Create inventory_categories table
CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create inventory_items table
CREATE TABLE IF NOT EXISTS inventory_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) UNIQUE,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    quantity INT DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'pcs',
    unit_price DECIMAL(10,2) DEFAULT 0,
    reorder_level INT DEFAULT 10,
    location_id INT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create inventory_transactions table
CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    transaction_type ENUM('IN', 'OUT', 'ADJUSTMENT') NOT NULL,
    quantity INT NOT NULL,
    notes TEXT,
    order_id INT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT IGNORE INTO inventory_categories (category_name, description) VALUES
('Spare Parts', 'Komponen pengganti untuk perangkat elektronik'),
('Accessories', 'Aksesoris tambahan'),
('Tools', 'Alat-alat reparasi'),
('Consumables', 'Bahan habis pakai');

-- Verify tables created
SELECT 'Inventory tables created successfully!' as Status;
SELECT COUNT(*) as categories_count FROM inventory_categories;
