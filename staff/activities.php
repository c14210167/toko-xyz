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

// Check permission - only owner or users with manage_roles permission can view activity logs
$permissionManager->requireAnyPermission(['manage_roles', 'manage_permissions'], 'dashboard.php');

// Get user roles
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';

// Get all employees for filter
$employees_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE user_type != 'customer' ORDER BY first_name";
$employees_stmt = $conn->prepare($employees_query);
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/activities.css">
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

            <!-- Motivational Quote Animation -->
            <div class="motivation-container">
                <span class="motivation-text" id="motivationText"></span>
                <span class="motivation-cursor">_</span>
            </div>
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
            <a href="roles.php" class="nav-item">
                <span class="nav-icon">🔑</span>
                <span class="nav-text">Manage Roles</span>
            </a>
            <a href="activities.php" class="nav-item active">
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
        <div class="page-header">
            <div>
                <h1>Activity Logs</h1>
                <p class="page-description">Track all employee activities and system changes</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <div class="filter-group">
                <label for="employeeFilter">Employee:</label>
                <select id="employeeFilter">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?php echo $employee['user_id']; ?>">
                            <?php echo htmlspecialchars($employee['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="actionFilter">Action Type:</label>
                <select id="actionFilter">
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="order_create">Order Created</option>
                    <option value="order_update">Order Updated</option>
                    <option value="role_create">Role Created</option>
                    <option value="role_update">Role Updated</option>
                    <option value="role_delete">Role Deleted</option>
                    <option value="permission_change">Permission Changed</option>
                    <option value="employee_update">Employee Updated</option>
                    <option value="location_create">Location Created</option>
                    <option value="location_update">Location Updated</option>
                    <option value="location_delete">Location Deleted</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="dateFilter">Date:</label>
                <select id="dateFilter">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month">Last 30 Days</option>
                    <option value="all">All Time</option>
                </select>
            </div>

            <button class="btn-refresh" onclick="loadActivities()">
                <span>🔄</span> Refresh
            </button>
        </div>

        <!-- Activity Timeline -->
        <div class="activities-container" id="activitiesContainer">
            <div class="loading-spinner">Loading activities...</div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer" style="display: none;">
            <button class="btn-page" id="btnPrevPage" onclick="previousPage()">← Previous</button>
            <span id="pageInfo"></span>
            <button class="btn-page" id="btnNextPage" onclick="nextPage()">Next →</button>
        </div>
    </div>

    <script src="../js/activities.js"></script>
    <script>
        // Motivational quotes typing animation
        const motivationalQuotes = [
            "Success is not final, failure is not fatal!",
            "Quality service builds trust!",
            "Every repair matters!",
            "Excellence in every fix!",
            "Customer satisfaction first!"
        ];

        let quoteIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        function typeMotivation() {
            const textElement = document.getElementById('motivationText');
            if (!textElement) return;

            const currentQuote = motivationalQuotes[quoteIndex];

            if (isDeleting) {
                textElement.textContent = currentQuote.substring(0, charIndex - 1);
                charIndex--;
                if (charIndex === 0) {
                    isDeleting = false;
                    quoteIndex = (quoteIndex + 1) % motivationalQuotes.length;
                    setTimeout(typeMotivation, 500);
                    return;
                }
            } else {
                textElement.textContent = currentQuote.substring(0, charIndex + 1);
                charIndex++;
                if (charIndex === currentQuote.length) {
                    isDeleting = true;
                    setTimeout(typeMotivation, 3000);
                    return;
                }
            }

            setTimeout(typeMotivation, isDeleting ? 40 : 80);
        }

        setTimeout(typeMotivation, 1000);
    </script>
</body>
</html>
