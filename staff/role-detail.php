<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Check if role_id is provided
if (!isset($_GET['id'])) {
    header('Location: roles.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permission
$permissionManager->requireAnyPermission(['manage_roles', 'manage_permissions'], 'dashboard.php');

$role_id = $_GET['id'];

// Get role details
$role_query = "SELECT * FROM roles WHERE role_id = :role_id";
$role_stmt = $conn->prepare($role_query);
$role_stmt->bindParam(':role_id', $role_id);
$role_stmt->execute();
$role = $role_stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
    header('Location: roles.php');
    exit();
}

// Get user count with this role
$user_count_query = "SELECT COUNT(*) as count FROM user_roles WHERE role_id = :role_id";
$user_count_stmt = $conn->prepare($user_count_query);
$user_count_stmt->bindParam(':role_id', $role_id);
$user_count_stmt->execute();
$user_count = $user_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

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
    <title>Role Detail - <?php echo htmlspecialchars($role['role_name']); ?></title>
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
            <div class="breadcrumb">
                <a href="roles.php">Roles</a>
                <span>›</span>
                <span><?php echo htmlspecialchars($role['role_name']); ?></span>
            </div>
            <div class="top-bar-actions">
                <button class="btn-primary" id="btnSavePermissions">
                    <span>💾</span>
                    <span>Save Changes</span>
                </button>
            </div>
        </div>

        <div class="content-area">
            <!-- Role Info Card -->
            <div class="role-info-card">
                <div class="role-header">
                    <div class="role-icon">🔑</div>
                    <div class="role-info">
                        <h2><?php echo htmlspecialchars($role['role_name']); ?></h2>
                        <p class="role-description"><?php echo htmlspecialchars($role['description'] ?? 'No description'); ?></p>
                        <?php if ($role['is_system_role']): ?>
                        <span class="system-badge">System Role</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="role-stats">
                    <div class="stat-item">
                        <span class="stat-label">Users with this role:</span>
                        <span class="stat-value"><?php echo $user_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Created:</span>
                        <span class="stat-value"><?php echo date('d M Y', strtotime($role['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="permissions-section">
                <div class="section-header">
                    <h3>🔐 Manage Permissions</h3>
                    <div class="quick-actions">
                        <button class="btn-secondary btn-sm" id="btnSelectAll">Select All</button>
                        <button class="btn-secondary btn-sm" id="btnDeselectAll">Deselect All</button>
                    </div>
                </div>

                <div class="permissions-grid" id="permissionsGrid">
                    <div class="loading-spinner">Loading permissions...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roleId = <?php echo $role_id; ?>;
        const isSystemRole = <?php echo $role['is_system_role'] ? 'true' : 'false'; ?>;
    </script>
    <script src="../js/role-management.js"></script>
</body>
</html>
