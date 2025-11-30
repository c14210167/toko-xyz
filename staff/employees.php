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

// Check if current user is owner
$is_owner = $_SESSION['user_type'] == 'owner';

// Get all roles for filter dropdown
$all_roles_query = "SELECT role_id, role_name FROM roles WHERE role_name != 'Customer' ORDER BY role_name";
$all_roles_stmt = $conn->prepare($all_roles_query);
$all_roles_stmt->execute();
$all_roles = $all_roles_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Plus Plus Komputer</title>
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
            <h1>Manage Employees</h1>
        </div>

        <div class="content-area">
            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <div class="search-box">
                    <input type="text" id="searchEmployee" placeholder="🔍 Search employees by name or email..." />
                </div>

                <div class="filter-row">
                    <div class="filter-group">
                        <label>Filter by Role:</label>
                        <select id="roleFilter">
                            <option value="all">All Roles</option>
                            <?php foreach ($all_roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_name']); ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Filter by Status:</label>
                        <select id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="showAddEmployeeModal()">
                        + Add New Employee
                    </button>
                </div>
            </div>

            <!-- Employee Cards -->
            <div class="employee-grid" id="employeeGrid">
                <div class="loading-spinner">Loading employees...</div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Employee</h2>
                <button class="btn-close" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" id="employeeId" name="employee_id">

                    <div class="form-group">
                        <label for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>

                    <div class="form-group" id="passwordGroup">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <div class="radio-group" id="roleRadioGroup">
                            <?php foreach ($all_roles as $role): ?>
                                <?php
                                // Only owners can assign Owner role
                                if ($role['role_name'] == 'Owner' && !$is_owner) {
                                    continue; // Skip Owner role for non-owners
                                }
                                ?>
                            <div class="radio-item">
                                <input
                                    type="radio"
                                    id="role_<?php echo $role['role_id']; ?>"
                                    name="role_id"
                                    value="<?php echo $role['role_id']; ?>"
                                    required
                                >
                                <label for="role_<?php echo $role['role_id']; ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$is_owner): ?>
                        <small style="color: #64748b; margin-top: 0.5rem; display: block;">
                            Note: Only owners can assign the Owner role
                        </small>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEmployeeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEmployee()">Save Employee</button>
            </div>
        </div>
    </div>

    <script src="../js/employee-management.js"></script>
</body>
</html>
