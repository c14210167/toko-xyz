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
    <title>Manage Locations - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/location-management.css">
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
                <a href="locations.php" class="nav-item active">
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
                <?php if (hasPermission('view_products')): ?>
                <a href="products.php" class="nav-item">
                    <span class="nav-icon">🛍️</span>
                    <span class="nav-text">Products</span>
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
                    <h1>Manage Locations</h1>
                    <p class="page-description">Manage service center locations and branches</p>
                </div>
                <button class="btn-add-location" onclick="showAddLocationModal()">
                    <span>➕</span> Add New Location
                </button>
            </div>

            <div class="locations-grid" id="locationsGrid">
                <div class="loading-spinner">Loading locations...</div>
            </div>
    </div>

    <!-- Add Location Modal -->
    <div id="addLocationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Location</h2>
                <button class="btn-close" onclick="closeAddLocationModal()">×</button>
            </div>
            <form id="addLocationForm" onsubmit="handleAddLocation(event)">
                <div class="form-group">
                    <label for="locationName">Location Name <span class="required">*</span></label>
                    <input type="text" id="locationName" name="name" required
                           placeholder="e.g., Main Branch, Downtown Store">
                </div>
                <div class="form-group">
                    <label for="locationAddress">Address</label>
                    <textarea id="locationAddress" name="address" rows="3"
                              placeholder="Full address of the location"></textarea>
                </div>
                <div class="form-group">
                    <label for="locationPhone">Phone</label>
                    <input type="tel" id="locationPhone" name="phone"
                           placeholder="e.g., 0812-3456-7890">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddLocationModal()">Cancel</button>
                    <button type="submit" class="btn-save">Add Location</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Location Modal -->
    <div id="editLocationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Location</h2>
                <button class="btn-close" onclick="closeEditLocationModal()">×</button>
            </div>
            <form id="editLocationForm" onsubmit="handleEditLocation(event)">
                <input type="hidden" id="editLocationId" name="location_id">
                <div class="form-group">
                    <label for="editLocationName">Location Name <span class="required">*</span></label>
                    <input type="text" id="editLocationName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editLocationAddress">Address</label>
                    <textarea id="editLocationAddress" name="address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="editLocationPhone">Phone</label>
                    <input type="tel" id="editLocationPhone" name="phone">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditLocationModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/location-management.js"></script>
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
