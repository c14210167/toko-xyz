<?php
session_start();
require_once '../config/init_permissions.php';

// Check permission - using inventory permission for now
if (!hasPermission('view_inventory')) {
    header('Location: dashboard.php');
    exit();
}

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';

// Motivational quotes
$motivational_quotes = [
    'Keep pushing forward!',
    'You are doing great!',
    'Excellence is a habit',
    'Make today count',
    'Stay focused, stay strong'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css?v=5">
    <link rel="stylesheet" href="../css/suppliers.css?v=5">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-profile">
                <div class="profile-picture">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="status-indicator"></div>
                </div>
                <h3 class="user-name"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="user-role">Staff</p>

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
                <a href="suppliers.php" class="nav-item active">
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
            </nav>

            <div class="sidebar-footer">
                <a href="../logout.php" class="footer-btn btn-logout">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1>🏢 Suppliers Management</h1>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <span>➕</span>
                    <span>Add New Supplier</span>
                </button>
            </div>

            <div class="content-area">
                <div class="suppliers-grid" id="suppliersGrid">
                    <div class="loading-state">Loading suppliers...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Supplier</h2>
            <form id="supplierForm">
                <input type="hidden" id="supplierId" name="supplier_id">

                <div class="form-group">
                    <label for="supplierName">Supplier Name *</label>
                    <input type="text" id="supplierName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="supplierDescription">Description</label>
                    <textarea id="supplierDescription" name="description" rows="3" placeholder="Brief description about the supplier..."></textarea>
                </div>

                <div class="form-group">
                    <label for="supplierAddress">Address</label>
                    <textarea id="supplierAddress" name="address" rows="3" placeholder="Full address of the supplier..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span id="submitIcon">💾</span>
                        <span id="submitText">Save Supplier</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Delete Supplier?</h2>
            <p>Are you sure you want to delete this supplier? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()">
                    <span>🗑️</span>
                    <span>Delete</span>
                </button>
            </div>
        </div>
    </div>

    <script src="../js/suppliers.js"></script>
    <script>
        const motivationalQuotes = <?php echo json_encode($motivational_quotes); ?>;

        // Motivational quotes typing animation
        let quoteIndex = 0;
        let charIndex = 0;
        let isDeleting = false;

        function typeMotivation() {
            const textElement = document.getElementById('motivationText');
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(typeMotivation, 1000);
            loadSuppliers();
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
