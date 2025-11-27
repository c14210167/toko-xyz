-- ============================================
-- ROLE-BASED ACCESS CONTROL (RBAC) SYSTEM
-- ============================================

-- 1. Roles table - Define system roles
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_system_role TINYINT(1) DEFAULT 1, -- System roles cannot be deleted
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Permissions table - Define all possible permissions
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    permission_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50), -- e.g., 'orders', 'users', 'inventory', 'reports'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permission_key (permission_key),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Role Permissions - Map permissions to roles
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. User Roles - Assign roles to users
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. User Permissions - Override permissions for specific users
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    is_granted TINYINT(1) NOT NULL DEFAULT 1, -- 1=granted, 0=revoked
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA - DEFAULT ROLES
-- ============================================

INSERT INTO roles (role_name, description, is_system_role) VALUES
('Owner', 'Full access to all system features and settings', 1),
('Manager', 'Access to most features except critical user management', 1),
('Technician', 'Access to orders, inventory, and technical features', 1),
('Cashier', 'Access to sales, orders, and payment processing', 1),
('Customer', 'Limited access to customer-facing features', 1)
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- ============================================
-- SEED DATA - PERMISSIONS
-- ============================================

-- Orders Permissions
INSERT INTO permissions (permission_key, permission_name, description, category) VALUES
('view_orders', 'View Orders', 'Can view order list and details', 'orders'),
('create_orders', 'Create Orders', 'Can create new orders', 'orders'),
('edit_orders', 'Edit Orders', 'Can edit order information', 'orders'),
('delete_orders', 'Delete Orders', 'Can delete orders', 'orders'),
('update_order_status', 'Update Order Status', 'Can change order status', 'orders'),
('assign_technician', 'Assign Technician', 'Can assign technician to orders', 'orders'),
('approve_order_cost', 'Approve Order Cost', 'Can approve order costs', 'orders'),

-- User Management Permissions
('view_users', 'View Users', 'Can view user list', 'users'),
('create_users', 'Create Users', 'Can create new users', 'users'),
('edit_users', 'Edit Users', 'Can edit user information', 'users'),
('delete_users', 'Delete Users', 'Can delete users', 'users'),
('manage_roles', 'Manage Roles', 'Can assign roles to users', 'users'),
('manage_permissions', 'Manage Permissions', 'Can manage user permissions', 'users'),

-- Inventory Permissions
('view_inventory', 'View Inventory', 'Can view inventory items', 'inventory'),
('create_inventory', 'Create Inventory Items', 'Can add new inventory items', 'inventory'),
('edit_inventory', 'Edit Inventory', 'Can edit inventory items', 'inventory'),
('delete_inventory', 'Delete Inventory', 'Can delete inventory items', 'inventory'),
('record_inventory_transaction', 'Record Inventory Transaction', 'Can record stock in/out', 'inventory'),
('view_low_stock_alerts', 'View Low Stock Alerts', 'Can view low stock alerts', 'inventory'),

-- Sales Permissions
('view_sales', 'View Sales', 'Can view sales transactions', 'sales'),
('create_sale', 'Create Sale', 'Can process sales', 'sales'),
('refund_sale', 'Refund Sale', 'Can process refunds', 'sales'),
('view_products', 'View Products', 'Can view product catalog', 'sales'),
('manage_products', 'Manage Products', 'Can add/edit/delete products', 'sales'),

-- Payment Permissions
('view_payments', 'View Payments', 'Can view payment records', 'payments'),
('record_payment', 'Record Payment', 'Can record payments', 'payments'),
('issue_refund', 'Issue Refund', 'Can issue refunds', 'payments'),
('generate_receipt', 'Generate Receipt', 'Can generate receipts', 'payments'),

-- Report Permissions
('view_reports', 'View Reports', 'Can view all reports', 'reports'),
('view_revenue_report', 'View Revenue Report', 'Can view revenue reports', 'reports'),
('view_pl_report', 'View P&L Report', 'Can view profit & loss reports', 'reports'),
('view_customer_analytics', 'View Customer Analytics', 'Can view customer analytics', 'reports'),
('export_reports', 'Export Reports', 'Can export reports to PDF/Excel', 'reports'),

-- Expense Permissions
('view_expenses', 'View Expenses', 'Can view expense records', 'expenses'),
('create_expense', 'Create Expense', 'Can add new expenses', 'expenses'),
('edit_expense', 'Edit Expense', 'Can edit expenses', 'expenses'),
('delete_expense', 'Delete Expense', 'Can delete expenses', 'expenses'),
('approve_expense', 'Approve Expense', 'Can approve expense requests', 'expenses'),

-- Customer Permissions
('view_customers', 'View Customers', 'Can view customer list', 'customers'),
('edit_customers', 'Edit Customers', 'Can edit customer information', 'customers'),
('delete_customers', 'Delete Customers', 'Can delete customers', 'customers'),
('view_customer_history', 'View Customer History', 'Can view customer order history', 'customers'),

-- Appointment Permissions
('view_appointments', 'View Appointments', 'Can view appointment schedule', 'appointments'),
('manage_appointments', 'Manage Appointments', 'Can create/edit/cancel appointments', 'appointments'),
('approve_appointments', 'Approve Appointments', 'Can approve appointment requests', 'appointments'),

-- Rating & Feedback Permissions
('view_ratings', 'View Ratings', 'Can view customer ratings', 'ratings'),
('respond_to_feedback', 'Respond to Feedback', 'Can respond to customer feedback', 'ratings'),

-- Settings Permissions
('view_settings', 'View Settings', 'Can view system settings', 'settings'),
('edit_settings', 'Edit Settings', 'Can modify system settings', 'settings'),
('manage_locations', 'Manage Locations', 'Can add/edit/delete locations', 'settings'),

-- Dashboard Permissions
('view_dashboard', 'View Dashboard', 'Can view dashboard', 'dashboard'),
('view_analytics', 'View Analytics', 'Can view detailed analytics', 'dashboard')

ON DUPLICATE KEY UPDATE
    permission_name=VALUES(permission_name),
    description=VALUES(description),
    category=VALUES(category);

-- ============================================
-- ASSIGN PERMISSIONS TO ROLES
-- ============================================

-- OWNER: Full access to everything
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Owner'
ON DUPLICATE KEY UPDATE role_id=role_id;

-- MANAGER: All except user management and critical settings
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Manager'
AND p.permission_key NOT IN ('delete_users', 'manage_roles', 'manage_permissions', 'edit_settings')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- TECHNICIAN: Orders, Inventory, Appointments
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Technician'
AND p.permission_key IN (
    'view_orders', 'edit_orders', 'update_order_status',
    'view_inventory', 'record_inventory_transaction', 'view_low_stock_alerts',
    'view_customers', 'view_customer_history',
    'view_appointments', 'manage_appointments',
    'view_dashboard', 'view_ratings'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- CASHIER: Sales, Orders, Payments
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Cashier'
AND p.permission_key IN (
    'view_orders', 'create_orders', 'update_order_status',
    'view_sales', 'create_sale', 'view_products',
    'view_payments', 'record_payment', 'generate_receipt',
    'view_customers', 'view_customer_history',
    'view_appointments',
    'view_dashboard'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- CUSTOMER: Limited access
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Customer'
AND p.permission_key IN (
    'view_orders', 'view_appointments'
)
ON DUPLICATE KEY UPDATE role_id=role_id;

-- ============================================
-- ASSIGN DEFAULT ROLES TO EXISTING USERS
-- ============================================

-- Assign Owner role to users with role='owner'
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'owner' AND r.role_name = 'Owner'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Manager role to users with role='manager'
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'manager' AND r.role_name = 'Manager'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Technician role to users with role='staff' or 'technician'
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role IN ('staff', 'technician') AND r.role_name = 'Technician'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Customer role to users with role='customer'
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'customer' AND r.role_name = 'Customer'
ON DUPLICATE KEY UPDATE user_id=user_id;
