<?php
session_start();
require_once '../config/init_permissions.php';

// Check permission
if (!hasPermission('view_inventory')) {
    header('Location: dashboard.php');
    exit();
}

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';

// Get locations for filter
require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Determine primary role
if ($_SESSION['user_type'] == 'owner') {
    $primary_role = 'Owner';
} else {
    $roles = $permissionManager->getUserRoles();
    $role_names = array_map(function($role) { return $role['role_name']; }, $roles);
    $primary_role = !empty($role_names) ? $role_names[0] : 'Staff';
}

$loc_query = "SELECT * FROM locations ORDER BY name";
$loc_stmt = $conn->prepare($loc_query);
$loc_stmt->execute();
$locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$cat_query = "SELECT * FROM inventory_categories ORDER BY category_name";
$cat_stmt = $conn->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css?v=7">
    <style>
        .inventory-container {
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .inventory-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .inventory-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .inventory-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .inventory-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .inventory-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .inventory-table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-low {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-normal {
            background: #d1fae5;
            color: #059669;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .action-btns {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-card.danger .value {
            color: #ef4444;
        }
    </style>
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
            <a href="inventory.php" class="nav-item active">
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

    <div class="main-content">
        <div class="inventory-container">
            <div class="page-header">
                <h1>📦 Inventory Management</h1>
                <?php if (hasPermission('create_inventory')): ?>
                <button class="btn btn-primary" onclick="showAddModal()">
                    + Add New Item
                </button>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Items</h3>
                    <div class="value" id="totalItems">-</div>
                </div>
                <div class="stat-card">
                    <h3>Total Value</h3>
                    <div class="value" id="totalValue">Rp 0</div>
                </div>
                <div class="stat-card danger">
                    <h3>Low Stock Items</h3>
                    <div class="value" id="lowStockCount">-</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchInput" placeholder="Item name or code...">
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Location</label>
                    <select id="locationFilter">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Stock Status</label>
                    <select id="lowStockFilter">
                        <option value="">All Items</option>
                        <option value="true">Low Stock Only</option>
                    </select>
                </div>
                <div class="filter-group" style="align-self: flex-end;">
                    <button class="btn btn-primary" onclick="loadInventory()">🔍 Filter</button>
                </div>
            </div>

            <!-- Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr>
                            <td colspan="9" class="loading">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Item</h2>
            <form id="addItemForm">
                <div class="form-group">
                    <label>Item Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Initial Quantity *</label>
                    <input type="number" name="quantity" value="0" required>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <input type="text" name="unit" value="pcs">
                </div>
                <div class="form-group">
                    <label>Unit Price (Rp)</label>
                    <input type="number" name="unit_price" value="0">
                </div>
                <div class="form-group">
                    <label>Reorder Level</label>
                    <input type="number" name="reorder_level" value="10">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <select name="location_id">
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <h2>Record Transaction</h2>
            <form id="transactionForm">
                <input type="hidden" name="item_id" id="transItemId">
                <div class="form-group">
                    <label>Item: <strong id="transItemName"></strong></label>
                    <p>Current Stock: <strong id="transCurrentStock"></strong></p>
                </div>
                <div class="form-group">
                    <label>Transaction Type *</label>
                    <select name="transaction_type" required>
                        <option value="IN">Stock IN (Purchase)</option>
                        <option value="OUT">Stock OUT (Used)</option>
                        <option value="ADJUSTMENT">Adjustment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeTransactionModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Price Modal -->
    <div id="editPriceModal" class="modal">
        <div class="modal-content">
            <h2>Edit Price</h2>
            <form id="editPriceForm">
                <input type="hidden" name="item_id" id="priceItemId">
                <div class="form-group">
                    <label>Item: <strong id="priceItemName"></strong></label>
                    <p>Current Price: <strong id="priceCurrentPrice"></strong></p>
                </div>
                <div class="form-group">
                    <label>New Selling Price (Rp) *</label>
                    <input type="number" name="selling_price" id="newSellingPrice" required min="0" step="1">
                </div>
                <div class="form-group">
                    <label>New Unit Price (Rp) *</label>
                    <input type="number" name="unit_price" id="newUnitPrice" required min="0" step="1">
                </div>
                <div class="form-group">
                    <label>Reason for Price Change *</label>
                    <textarea name="notes" rows="3" required placeholder="e.g., Market price adjustment, Supplier price change, etc."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="closeEditPriceModal()">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Price</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load inventory on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadInventory();
            loadStats();

            // Auto-filter on input
            document.getElementById('searchInput').addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    loadInventory();
                }
            });
        });

        function loadInventory() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const location = document.getElementById('locationFilter').value;
            const lowStock = document.getElementById('lowStockFilter').value;

            const params = new URLSearchParams({
                search: search,
                category: category,
                location: location,
                low_stock: lowStock
            });

            fetch(`api/get-inventory.php?${params}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        displayInventory(data.items);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to load inventory');
                });
        }

        function displayInventory(items) {
            const tbody = document.getElementById('inventoryTableBody');

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <div>📦</div>
                            <p>No items found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = items.map(item => `
                <tr>
                    <td>${item.item_code || '-'}</td>
                    <td><strong>${item.name}</strong></td>
                    <td>${item.category_name || '-'}</td>
                    <td>${item.quantity} ${item.unit}</td>
                    <td>Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}</td>
                    <td>Rp ${(item.quantity * item.unit_price).toLocaleString('id-ID')}</td>
                    <td>${item.location_name || '-'}</td>
                    <td>
                        ${item.quantity <= item.reorder_level
                            ? '<span class="badge badge-low">Low Stock</span>'
                            : '<span class="badge badge-normal">Normal</span>'}
                    </td>
                    <td class="action-btns">
                        <?php if (hasPermission('record_inventory_transaction')): ?>
                        <button class="btn btn-sm btn-success" onclick="showTransactionModal(${item.item_id}, '${item.name}', ${item.quantity})">
                            📝 Transaction
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="showEditPriceModal(${item.item_id}, '${item.name}', ${item.unit_price})">
                            💰 Edit Price
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
            `).join('');
        }

        function loadStats() {
            fetch('api/get-inventory.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const items = data.items;
                        const totalItems = items.length;
                        const totalValue = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                        const lowStock = items.filter(item => item.quantity <= item.reorder_level).length;

                        document.getElementById('totalItems').textContent = totalItems;
                        document.getElementById('totalValue').textContent = 'Rp ' + totalValue.toLocaleString('id-ID');
                        document.getElementById('lowStockCount').textContent = lowStock;
                    }
                });
        }

        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addItemForm').reset();
        }

        function showTransactionModal(itemId, itemName, currentStock) {
            document.getElementById('transItemId').value = itemId;
            document.getElementById('transItemName').textContent = itemName;
            document.getElementById('transCurrentStock').textContent = currentStock;
            document.getElementById('transactionModal').classList.add('active');
        }

        function closeTransactionModal() {
            document.getElementById('transactionModal').classList.remove('active');
            document.getElementById('transactionForm').reset();
        }

        function showEditPriceModal(itemId, itemName, currentPrice) {
            document.getElementById('priceItemId').value = itemId;
            document.getElementById('priceItemName').textContent = itemName;
            document.getElementById('priceCurrentPrice').textContent = 'Rp ' + currentPrice.toLocaleString('id-ID');
            document.getElementById('newSellingPrice').value = currentPrice;
            document.getElementById('newUnitPrice').value = currentPrice;
            document.getElementById('editPriceModal').classList.add('active');
        }

        function closeEditPriceModal() {
            document.getElementById('editPriceModal').classList.remove('active');
            document.getElementById('editPriceForm').reset();
        }

        // Add Item Form Submit
        document.getElementById('addItemForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            fetch('api/add-inventory-item.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Item added successfully!');
                    closeAddModal();
                    loadInventory();
                    loadStats();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to add item');
            });
        });

        // Transaction Form Submit
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            fetch('api/record-inventory-transaction.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction recorded successfully!');
                    closeTransactionModal();
                    loadInventory();
                    loadStats();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to record transaction');
            });
        });

        // Edit Price Form Submit
        document.getElementById('editPriceForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            if (!confirm('Are you sure you want to update the price for this item?')) {
                return;
            }

            fetch('api/update-product-price.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Price updated successfully!');
                    closeEditPriceModal();
                    loadInventory();
                    loadStats();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to update price');
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
    </div><!-- End dashboard-container -->
</body>
</html>
