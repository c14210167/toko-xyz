-- ============================================
-- QUICK INSTALL - Essential Tables Only
-- Run this in phpMyAdmin if installer doesn't work
-- ============================================

USE xyz_service;

SET FOREIGN_KEY_CHECKS=0;

-- ============================================
-- 1. RBAC TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_system_role TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    permission_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    is_granted TINYINT(1) NOT NULL DEFAULT 1,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. INVENTORY TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- ============================================
-- 3. NOTIFICATIONS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    icon VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Insert Roles
INSERT IGNORE INTO roles (role_name, description, is_system_role) VALUES
('Owner', 'Full access to all system features', 1),
('Manager', 'Access to most features except critical settings', 1),
('Technician', 'Access to orders and inventory', 1),
('Cashier', 'Access to sales and payments', 1),
('Customer', 'Customer access only', 1);

-- Insert Inventory Categories
INSERT IGNORE INTO inventory_categories (category_name, description) VALUES
('Spare Parts', 'Komponen pengganti untuk perangkat elektronik'),
('Accessories', 'Aksesoris tambahan'),
('Tools', 'Alat-alat reparasi'),
('Consumables', 'Bahan habis pakai');

-- Insert Essential Permissions
INSERT IGNORE INTO permissions (permission_key, permission_name, description, category) VALUES
-- Dashboard
('view_dashboard', 'View Dashboard', 'Can view dashboard', 'dashboard'),

-- Orders
('view_orders', 'View Orders', 'Can view orders', 'orders'),
('create_orders', 'Create Orders', 'Can create orders', 'orders'),
('edit_orders', 'Edit Orders', 'Can edit orders', 'orders'),
('update_order_status', 'Update Order Status', 'Can update order status', 'orders'),

-- Customers
('view_customers', 'View Customers', 'Can view customers', 'customers'),

-- Inventory
('view_inventory', 'View Inventory', 'Can view inventory', 'inventory'),
('create_inventory', 'Create Inventory Items', 'Can add inventory items', 'inventory'),
('record_inventory_transaction', 'Record Inventory Transaction', 'Can record stock transactions', 'inventory'),

-- Products
('view_products', 'View Products', 'Can view products', 'products'),
('manage_products', 'Manage Products', 'Can manage products', 'products'),

-- Sales
('view_sales', 'View Sales', 'Can view sales', 'sales'),
('create_sale', 'Create Sale', 'Can create sales', 'sales'),

-- Expenses
('view_expenses', 'View Expenses', 'Can view expenses', 'expenses'),
('create_expense', 'Create Expense', 'Can create expenses', 'expenses'),

-- Reports
('view_reports', 'View Reports', 'Can view reports', 'reports'),

-- Permissions
('manage_permissions', 'Manage Permissions', 'Can manage user permissions', 'permissions'),
('manage_roles', 'Manage Roles', 'Can manage roles', 'permissions');

-- Assign ALL permissions to Owner role
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Owner';

-- Assign Owner role to existing owner users
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM users u
CROSS JOIN roles r
WHERE u.role = 'owner' AND r.role_name = 'Owner';

SET FOREIGN_KEY_CHECKS=1;

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 'Tables Created:' as Status;
SELECT COUNT(*) as roles_count FROM roles;
SELECT COUNT(*) as permissions_count FROM permissions;
SELECT COUNT(*) as inventory_categories_count FROM inventory_categories;
SELECT COUNT(*) as user_roles_count FROM user_roles;

SELECT 'Installation Complete!' as Message;
