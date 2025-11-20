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

// Get all locations for filter
$locations_query = "SELECT location_id, name FROM locations ORDER BY name";
$locations_stmt = $conn->prepare($locations_query);
$locations_stmt->execute();
$locations = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/staff-orders.css">
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
            <a href="orders.php" class="nav-item active">
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
            <h1>Orders Management</h1>
            <div class="top-bar-actions">
                <div class="notification-badge">
                    <span class="badge-icon">🔔</span>
                    <span class="badge-count" id="notifCount">0</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Cari order number, customer name, phone..." />
                </div>
                <div class="filter-controls">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="waiting_parts">Waiting Parts</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="locationFilter" class="filter-select">
                        <option value="all">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location['location_id']; ?>">
                                <?php echo htmlspecialchars($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select id="sortBy" class="filter-select">
                        <option value="created_desc">Newest First</option>
                        <option value="created_asc">Oldest First</option>
                        <option value="updated_desc">Recently Updated</option>
                        <option value="priority">High Priority</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Service Type</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="8" class="loading-cell">
                                <div class="loading-spinner"></div>
                                Loading orders...
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

    <!-- Order Detail Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Order Details</h2>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="modal">
        <div class="modal-content modal-chat">
            <div class="modal-header">
                <div class="chat-header-info">
                    <h2>💬 Chat with <span id="chatCustomerName"></span></h2>
                    <p class="chat-order-number">Order: <span id="chatOrderNumber"></span></p>
                </div>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body chat-modal-body">
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="chat-input-container">
                    <textarea id="chatInput" placeholder="Type your message..." rows="2"></textarea>
                    <button id="chatSendBtn" class="btn btn-primary">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/staff-orders.js"></script>
</body>
</html>
