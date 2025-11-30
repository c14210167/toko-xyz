<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';
require_once '../config/init_permissions.php';

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permission - only owner or users with manage_roles permission
$permissionManager->requireAnyPermission(['manage_roles', 'manage_permissions'], 'dashboard.php');

// Get user roles
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/role-management.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div class="status-indicator"></div>
            </div>
            <h3 class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            <p class="user-role"><?php echo htmlspecialchars($primary_role); ?></p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="pos.php" class="nav-item">
                <span class="nav-icon">💳</span>
                <span class="nav-text">Point of Sale</span>
            </a>
            <a href="session-history.php" class="nav-item">
                <span class="nav-icon">📜</span>
                <span class="nav-text">Session History</span>
            </a>
            <a href="orders.php" class="nav-item">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="customers.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
            <a href="inventory.php" class="nav-item">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Inventory</span>
            </a>
            <a href="inventory-taking.php" class="nav-item">
                <span class="nav-icon">📋</span>
                <span class="nav-text">Inventory Taking</span>
            </a>
            <a href="suppliers.php" class="nav-item">
                <span class="nav-icon">🏢</span>
                <span class="nav-text">Suppliers</span>
            </a>
            <?php if (hasPermission('manage_roles') || hasPermission('manage_permissions')): ?>
            <a href="employees.php" class="nav-item">
                <span class="nav-icon">👨‍💼</span>
                <span class="nav-text">Manage Employees</span>
            </a>
            <a href="locations.php" class="nav-item">
                <span class="nav-icon">📍</span>
                <span class="nav-text">Manage Locations</span>
            </a>
            <a href="roles.php" class="nav-item active">
                <span class="nav-icon">🔑</span>
                <span class="nav-text">Manage Roles</span>
            </a>
            <a href="activities.php" class="nav-item">
                <span class="nav-icon">📋</span>
                <span class="nav-text">Activity Logs</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('view_sales')): ?>
            <a href="sales.php" class="nav-item">
                <span class="nav-icon">💰</span>
                <span class="nav-text">Sales</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('view_reports')): ?>
            <a href="reports.php" class="nav-item">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Reports</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="view-as-customer.php" class="footer-btn btn-customer">
                <span>👤</span>
                <span>View as Customer</span>
            </a>
            <a href="../logout.php" class="footer-btn btn-logout">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1>Manage Roles</h1>
            <div class="top-bar-actions">
                <button class="btn-primary" id="btnAddRole">
                    <span>➕</span>
                    <span>Add New Role</span>
                </button>
            </div>
        </div>

        <div class="content-area">
            <div class="info-banner">
                <span class="info-icon">ℹ️</span>
                <p>Roles define a set of permissions. System roles (Owner, Manager, Technician, Cashier, Customer) cannot be deleted but can be modified.</p>
            </div>

            <!-- Roles Grid -->
            <div class="roles-grid" id="rolesGrid">
                <div class="loading-spinner">Loading roles...</div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div class="modal" id="addRoleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Role</h3>
                <button class="modal-close" id="closeAddModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="roleName">Role Name:</label>
                    <input type="text" id="roleName" placeholder="e.g., Supervisor" required>
                </div>
                <div class="form-group">
                    <label for="roleDescription">Description:</label>
                    <textarea id="roleDescription" rows="3" placeholder="Brief description of this role"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="cancelAddRole">Cancel</button>
                <button class="btn-primary" id="confirmAddRole">Create Role</button>
            </div>
        </div>
    </div>

    <!-- Delete Role Modal -->
    <div class="modal" id="deleteRoleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Role</h3>
                <button class="modal-close" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this role? This action cannot be undone.</p>
                <p class="warning-text">⚠️ Users with this role will lose associated permissions.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="cancelDeleteRole">Cancel</button>
                <button class="btn-danger" id="confirmDeleteRole">Delete Role</button>
            </div>
        </div>
    </div>

    <script src="../js/role-management.js"></script>
</body>
</html>
