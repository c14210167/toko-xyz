-- Complete setup for suppliers table
-- Run this entire script in phpMyAdmin

-- Step 1: Check and drop existing table if needed (uncomment if you want to recreate)
-- DROP TABLE IF EXISTS suppliers;

-- Step 2: Create suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Insert sample data (optional)
INSERT INTO suppliers (name, description, address) VALUES
('Tech Components Indonesia', 'Supplier of computer components and accessories', 'Jl. Mangga Dua Raya No. 123, Jakarta Utama'),
('Global Hardware Supply', 'International hardware supplier with competitive prices', 'Komplek ITC Mangga Dua, Blok C No. 45, Jakarta'),
('Digital Parts Co.', 'Specialized in laptop parts and LCD screens', 'Jl. Gajah Mada No. 88, Jakarta Pusat');

-- Verify the table was created successfully
SELECT 'Suppliers table created successfully!' as message;
SELECT COUNT(*) as total_suppliers FROM suppliers;
