<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

// Check if staff or owner
if ($_SESSION['user_type'] == 'customer') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Get user roles
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';

// Motivational quotes
$motivational_quotes = [
    'Keep pushing forward!',
    'You are doing great!',
    'Excellence is a habit',
    'Make today count',
    'Stay focused, stay strong',
    'Success is near',
    'Believe in yourself',
    'Dream big, work hard',
    'You got this!',
    'Be the best version',
    'Progress over perfection',
    'Stay positive always'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.png" alt="Plus Plus Komputer" class="sidebar-logo">
        </div>

        <div class="user-profile">
            <div class="profile-picture">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <div class="status-indicator"></div>
            </div>
            <h3 class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            <p class="user-role"><?php echo htmlspecialchars($primary_role); ?></p>
            
            <!-- Motivational Quote Animation -->
            <div class="motivation-container">
                <span class="motivation-text" id="motivationText"></span>
                <span class="motivation-cursor">_</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
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
            <a href="reports.php" class="nav-item">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Reports</span>
            </a>
            <a href="settings.php" class="nav-item">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">Settings</span>
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
            <h1>Dashboard</h1>
            <div class="top-bar-actions">
                <div class="notification-badge">
                    <span class="badge-icon">🔔</span>
                    <span class="badge-count">3</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>! 👋</h2>
                <p>Here's what's happening with your store today.</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3>12</h3>
                        <p>Active Orders</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>45</h3>
                        <p>Completed Today</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3>8</h3>
                        <p>Pending Parts</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>Rp 15.2M</h3>
                        <p>Revenue Today</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-section">
                <h3>Recent Activity</h3>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">🔧</div>
                        <div class="activity-content">
                            <p><strong>New order received</strong> - SRV-000123</p>
                            <span class="activity-time">5 minutes ago</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <p><strong>Order completed</strong> - SRV-000120</p>
                            <span class="activity-time">15 minutes ago</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">📦</div>
                        <div class="activity-content">
                            <p><strong>Parts arrived</strong> - LCD ASUS ROG</p>
                            <span class="activity-time">1 hour ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass motivational quotes to JavaScript
        const motivationalQuotes = <?php echo json_encode($motivational_quotes); ?>;
    </script>
    <script src="../js/staff-dashboard.js"></script>
</body>
</html>