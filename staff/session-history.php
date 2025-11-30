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

// Check permission
$permissionManager->requireAnyPermission(['access_pos', 'create_transaction', 'view_all_orders'], 'dashboard.php');

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
    <title>Session History - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/session-history.css">
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
            <a href="session-history.php" class="nav-item active">
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
            <a href="roles.php" class="nav-item">
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
            <h1>📜 POS Session History</h1>
        </div>

        <div class="content-area">
            <!-- Filter Section -->
            <div class="filter-section">
                <select id="statusFilter" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="open">Open</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <!-- Sessions Table -->
            <div class="sessions-container">
                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Cashier</th>
                            <th>Location</th>
                            <th>Opened At</th>
                            <th>Closed At</th>
                            <th>Variance</th>
                            <th>Transactions</th>
                            <th>Total Sales</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsTableBody">
                        <tr>
                            <td colspan="10" class="loading-cell">
                                <div class="loading-spinner"></div>
                                Loading sessions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <!-- Pagination will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Session Detail Modal -->
    <div id="sessionDetailModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Session Details - <span id="modalSessionId"></span></h2>
                <span class="modal-close" onclick="closeSessionDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="sessionDetailBody">
                <!-- Session details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../js/session-history.js"></script>
</body>
</html>
