-- ============================================
-- SEED PERMISSIONS DATA
-- Run this after comprehensive-database-schema.sql
-- ============================================

-- Insert all permissions
INSERT INTO permissions (permission_key, permission_name, description, category) VALUES
-- Orders
('view_orders', 'View Orders', 'Can view order list and details', 'orders'),
('create_orders', 'Create Orders', 'Can create new orders', 'orders'),
('edit_orders', 'Edit Orders', 'Can edit order information', 'orders'),
('delete_orders', 'Delete Orders', 'Can delete orders', 'orders'),
('update_order_status', 'Update Order Status', 'Can change order status', 'orders'),
('assign_technician', 'Assign Technician', 'Can assign technician to orders', 'orders'),
('approve_order_cost', 'Approve Order Cost', 'Can approve order costs', 'orders'),

-- Users
('view_users', 'View Users', 'Can view user list', 'users'),
('create_users', 'Create Users', 'Can create new users', 'users'),
('edit_users', 'Edit Users', 'Can edit user information', 'users'),
('delete_users', 'Delete Users', 'Can delete users', 'users'),
('manage_roles', 'Manage Roles', 'Can assign roles to users', 'users'),
('manage_permissions', 'Manage Permissions', 'Can manage user permissions', 'users'),

-- Inventory
('view_inventory', 'View Inventory', 'Can view inventory items', 'inventory'),
('create_inventory', 'Create Inventory Items', 'Can add new inventory items', 'inventory'),
('edit_inventory', 'Edit Inventory', 'Can edit inventory items', 'inventory'),
('delete_inventory', 'Delete Inventory', 'Can delete inventory items', 'inventory'),
('record_inventory_transaction', 'Record Inventory Transaction', 'Can record stock in/out', 'inventory'),
('view_low_stock_alerts', 'View Low Stock Alerts', 'Can view low stock alerts', 'inventory'),

-- Sales
('view_sales', 'View Sales', 'Can view sales transactions', 'sales'),
('create_sale', 'Create Sale', 'Can process sales', 'sales'),
('refund_sale', 'Refund Sale', 'Can process refunds', 'sales'),
('view_products', 'View Products', 'Can view product catalog', 'sales'),
('manage_products', 'Manage Products', 'Can add/edit/delete products', 'sales'),

-- Payments
('view_payments', 'View Payments', 'Can view payment records', 'payments'),
('record_payment', 'Record Payment', 'Can record payments', 'payments'),
('issue_refund', 'Issue Refund', 'Can issue refunds', 'payments'),
('generate_receipt', 'Generate Receipt', 'Can generate receipts', 'payments'),

-- Reports
('view_reports', 'View Reports', 'Can view all reports', 'reports'),
('view_revenue_report', 'View Revenue Report', 'Can view revenue reports', 'reports'),
('view_pl_report', 'View P&L Report', 'Can view profit & loss reports', 'reports'),
('view_customer_analytics', 'View Customer Analytics', 'Can view customer analytics', 'reports'),
('export_reports', 'Export Reports', 'Can export reports to PDF/Excel', 'reports'),

-- Expenses
('view_expenses', 'View Expenses', 'Can view expense records', 'expenses'),
('create_expense', 'Create Expense', 'Can add new expenses', 'expenses'),
('edit_expense', 'Edit Expense', 'Can edit expenses', 'expenses'),
('delete_expense', 'Delete Expense', 'Can delete expenses', 'expenses'),
('approve_expense', 'Approve Expense', 'Can approve expense requests', 'expenses'),

-- Customers
('view_customers', 'View Customers', 'Can view customer list', 'customers'),
('edit_customers', 'Edit Customers', 'Can edit customer information', 'customers'),
('delete_customers', 'Delete Customers', 'Can delete customers', 'customers'),
('view_customer_history', 'View Customer History', 'Can view customer order history', 'customers'),

-- Appointments
('view_appointments', 'View Appointments', 'Can view appointment schedule', 'appointments'),
('manage_appointments', 'Manage Appointments', 'Can create/edit/cancel appointments', 'appointments'),
('approve_appointments', 'Approve Appointments', 'Can approve appointment requests', 'appointments'),

-- Ratings
('view_ratings', 'View Ratings', 'Can view customer ratings', 'ratings'),
('respond_to_feedback', 'Respond to Feedback', 'Can respond to customer feedback', 'ratings'),

-- Settings
('view_settings', 'View Settings', 'Can view system settings', 'settings'),
('edit_settings', 'Edit Settings', 'Can modify system settings', 'settings'),
('manage_locations', 'Manage Locations', 'Can add/edit/delete locations', 'settings'),

-- Dashboard
('view_dashboard', 'View Dashboard', 'Can view dashboard', 'dashboard'),
('view_analytics', 'View Analytics', 'Can view detailed analytics', 'dashboard')

ON DUPLICATE KEY UPDATE
    permission_name=VALUES(permission_name),
    description=VALUES(description),
    category=VALUES(category);

-- ============================================
-- ASSIGN PERMISSIONS TO ROLES
-- ============================================

-- OWNER: Full access
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Owner'
ON DUPLICATE KEY UPDATE role_id=role_id;

-- MANAGER: All except delete users, manage roles/permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Manager'
AND p.permission_key NOT IN ('delete_users', 'manage_roles', 'manage_permissions', 'edit_settings')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- TECHNICIAN: Orders, Inventory, Customers
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

-- CUSTOMER: Limited
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
-- MIGRATE EXISTING USERS TO RBAC SYSTEM
-- ============================================

-- Assign Owner role
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'owner' AND r.role_name = 'Owner'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Manager role
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'manager' AND r.role_name = 'Manager'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Technician role
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role IN ('staff', 'technician') AND r.role_name = 'Technician'
ON DUPLICATE KEY UPDATE user_id=user_id;

-- Assign Customer role
INSERT INTO user_roles (user_id, role_id, assigned_by)
SELECT u.user_id, r.role_id, NULL
FROM users u
CROSS JOIN roles r
WHERE u.role = 'customer' AND r.role_name = 'Customer'
ON DUPLICATE KEY UPDATE user_id=user_id;
