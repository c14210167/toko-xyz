<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';
require_once '../config/init_permissions.php';

// Check permission
if (!hasPermission('view_inventory')) {
    header('Location: dashboard.php');
    exit();
}

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';

// Get locations for dropdown
$database = new Database();
$conn = $database->getConnection();

// Get user roles
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';

$loc_query = "SELECT * FROM locations ORDER BY name";
$loc_stmt = $conn->prepare($loc_query);
$loc_stmt->execute();
$locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Inventory Taking - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css?v=7">
    <link rel="stylesheet" href="../css/inventory-taking.css?v=7">
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
                <a href="inventory-taking.php" class="nav-item active">
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
                <h1>📋 Inventory Taking</h1>
                <div class="header-info">
                    <div class="info-badge">
                        <span class="info-label">Total Items:</span>
                        <span class="info-value" id="totalItems">-</span>
                    </div>
                    <div class="info-badge">
                        <span class="info-label">Changes:</span>
                        <span class="info-value" id="changesCount">0</span>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <div class="taking-container">
                <div class="taking-table-wrapper">
                    <table class="taking-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 120px;">Item Code</th>
                                <th>Item Name</th>
                                <th style="width: 150px;">Category</th>
                                <th style="width: 120px;">Current Stock</th>
                                <th style="width: 150px;">New Stock</th>
                                <th style="width: 200px;">Stored In</th>
                            </tr>
                        </thead>
                        <tbody id="takingTableBody">
                            <tr>
                                <td colspan="7" class="loading-state">Loading inventory items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="actions-bar">
                    <button class="btn btn-secondary" onclick="discardChanges()">
                        <span>❌</span>
                        <span>Discard Changes</span>
                    </button>
                    <button class="btn btn-primary" onclick="saveChanges()">
                        <span>💾</span>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3>Discard the changes you've made?</h3>
            <p>All unsaved changes will be lost.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeConfirmModal()">No</button>
                <button class="btn btn-danger" onclick="confirmDiscard()">Yes</button>
            </div>
        </div>
    </div>

    <script>
        const locations = <?php echo json_encode($locations); ?>;
        const motivationalQuotes = <?php echo json_encode($motivational_quotes); ?>;
        let originalData = [];
        let currentData = [];

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

        // Load inventory on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(typeMotivation, 1000);
            loadInventory();
        });

        async function loadInventory() {
            try {
                const response = await fetch('api/get-inventory.php');
                const data = await response.json();

                if (data.success) {
                    originalData = JSON.parse(JSON.stringify(data.items)); // Deep copy
                    currentData = JSON.parse(JSON.stringify(data.items));
                    renderTable();
                    document.getElementById('totalItems').textContent = data.items.length;
                } else {
                    alert('Error loading inventory: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load inventory');
            }
        }

        function renderTable() {
            const tbody = document.getElementById('takingTableBody');

            if (currentData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">No inventory items found</td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = currentData.map((item, index) => {
                const isChanged = hasChanged(item);
                const rowClass = isChanged ? 'changed-row' : '';

                return `
                    <tr class="${rowClass}" data-item-id="${item.item_id}">
                        <td>${index + 1}</td>
                        <td>${item.item_code || '-'}</td>
                        <td><strong>${escapeHtml(item.name)}</strong></td>
                        <td>${escapeHtml(item.category_name || '-')}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td>
                            <input
                                type="number"
                                class="stock-input"
                                value="${item.quantity}"
                                placeholder="${item.quantity}"
                                data-item-id="${item.item_id}"
                                oninput="handleStockChange(${item.item_id}, this.value)"
                                min="0"
                            />
                        </td>
                        <td>
                            <input
                                type="text"
                                class="location-input"
                                value="${escapeHtml(item.shelf_location || '')}"
                                placeholder="e.g., Rack A, row 1"
                                data-item-id="${item.item_id}"
                                oninput="handleShelfLocationChange(${item.item_id}, this.value)"
                            />
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function hasChanged(item) {
            const original = originalData.find(i => i.item_id === item.item_id);
            if (!original) return false;

            return original.quantity != item.quantity ||
                   original.shelf_location != item.shelf_location;
        }

        function handleStockChange(itemId, newValue) {
            const item = currentData.find(i => i.item_id === itemId);
            if (item) {
                item.quantity = parseInt(newValue) || 0;
                updateChangesCount();
                highlightChangedRow(itemId);
            }
        }

        function handleShelfLocationChange(itemId, newShelfLocation) {
            const item = currentData.find(i => i.item_id === itemId);
            if (item) {
                item.shelf_location = newShelfLocation || '';
                updateChangesCount();
                highlightChangedRow(itemId);
            }
        }

        function highlightChangedRow(itemId) {
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            const item = currentData.find(i => i.item_id === itemId);

            if (row && item) {
                if (hasChanged(item)) {
                    row.classList.add('changed-row');
                } else {
                    row.classList.remove('changed-row');
                }
            }
        }

        function updateChangesCount() {
            const changedItems = currentData.filter(item => hasChanged(item));
            document.getElementById('changesCount').textContent = changedItems.length;
        }

        function discardChanges() {
            const changedItems = currentData.filter(item => hasChanged(item));

            if (changedItems.length === 0) {
                alert('No changes to discard');
                return;
            }

            document.getElementById('confirmModal').classList.add('active');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        function confirmDiscard() {
            currentData = JSON.parse(JSON.stringify(originalData));
            renderTable();
            updateChangesCount();
            closeConfirmModal();

            // Show success message
            showNotification('Changes discarded', 'info');
        }

        async function saveChanges() {
            const changedItems = currentData.filter(item => hasChanged(item));

            if (changedItems.length === 0) {
                alert('No changes to save');
                return;
            }

            // Prepare changes data
            const changes = changedItems.map(item => {
                const original = originalData.find(i => i.item_id === item.item_id);
                return {
                    item_id: item.item_id,
                    item_name: item.name,
                    old_quantity: original.quantity,
                    new_quantity: item.quantity,
                    old_shelf_location: original.shelf_location || '',
                    new_shelf_location: item.shelf_location || ''
                };
            });

            try {
                const response = await fetch('api/bulk-update-inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ changes })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Changes saved successfully!', 'success');

                    // Reload inventory to get fresh data
                    await loadInventory();
                } else {
                    alert('Error saving changes: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save changes');
            }
        }

        function showNotification(message, type) {
            // Simple notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });

        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            const changedItems = currentData.filter(item => hasChanged(item));
            if (changedItems.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
