<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Check if employee_id is provided
if (!isset($_GET['id'])) {
    header('Location: employees.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permission
$permissionManager->requireAnyPermission(['manage_roles', 'manage_permissions'], 'dashboard.php');

$employee_id = $_GET['id'];

// Get employee details
$employee_query = "SELECT user_id, first_name, last_name, email, phone, user_type as role, created_at FROM users WHERE user_id = :user_id AND user_type != 'customer'";
$employee_stmt = $conn->prepare($employee_query);
$employee_stmt->bindParam(':user_id', $employee_id);
$employee_stmt->execute();
$employee = $employee_stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: employees.php');
    exit();
}

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
    <title>Employee Detail - <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/employee-management.css">
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
            <a href="employees.php" class="nav-item active">
                <span class="nav-icon">👨‍💼</span>
                <span class="nav-text">Manage Employees</span>
            </a>
            <a href="locations.php" class="nav-item">
                <span class="nav-icon">📍</span>
                <span class="nav-text">Manage Locations</span>
            </a>
            <a href="roles.php" class="nav-item">
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
                <a href="employees.php">Employees</a>
                <span>›</span>
                <span><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
            </div>
        </div>

        <div class="content-area">
            <!-- Employee Profile Card -->
            <div class="employee-profile-card">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                        <p class="employee-email"><?php echo htmlspecialchars($employee['email']); ?></p>
                        <p class="employee-phone"><?php echo htmlspecialchars($employee['phone'] ?? 'No phone'); ?></p>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-row">
                        <span class="detail-label">Employee ID:</span>
                        <span class="detail-value">#<?php echo str_pad($employee['user_id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Old Role Type:</span>
                        <span class="detail-value badge-role"><?php echo ucfirst($employee['role']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Joined Date:</span>
                        <span class="detail-value"><?php echo date('d M Y', strtotime($employee['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Manage Privileges Section -->
            <div class="privileges-section">
                <div class="section-header">
                    <h3>🔐 Manage Privileges</h3>
                    <button class="btn-primary" id="btnSaveRoles">Save Changes</button>
                </div>

                <div class="current-roles-container">
                    <h4>Current Roles</h4>
                    <div class="roles-list" id="currentRolesList">
                        <div class="loading-spinner">Loading roles...</div>
                    </div>
                </div>

                <div class="available-roles-container">
                    <h4>Available Roles</h4>
                    <div class="roles-checkbox-list" id="availableRolesList">
                        <div class="loading-spinner">Loading available roles...</div>
                    </div>
                </div>
            </div>

            <!-- Activity History -->
            <div class="activity-history-section">
                <h3>📊 Recent Activity</h3>
                <div class="activity-list" id="activityList">
                    <p class="no-data">No recent activity</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const employeeId = <?php echo $employee_id; ?>;
    </script>
    <script src="../js/employee-management.js"></script>
</body>
</html>
