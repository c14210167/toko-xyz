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
    <link rel="stylesheet" href="../css/staff-dashboard.css?v=6">
    <link rel="stylesheet" href="../css/staff-orders.css?v=6">
    <link rel="stylesheet" href="../css/order-management.css?v=6">
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
            <h1>Orders Management</h1>
        </div>

        <div class="content-area">
            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <div class="search-row">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="🔍 Cari order number, customer name, phone..." />
                    </div>
                    <?php if (hasPermission('create_orders')): ?>
                    <button class="btn btn-primary" onclick="showMemberCheckModal()" style="white-space: nowrap;">
                        + New Order
                    </button>
                    <?php endif; ?>
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

    <!-- Member Check Modal -->
    <div id="memberCheckModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>🆕 Create New Order</h2>
                <span class="modal-close" onclick="closeMemberCheckModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p style="text-align: center; margin-bottom: 20px; font-size: 16px;">
                    Is this customer a registered member?
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button class="btn btn-primary" onclick="showMemberSearchModal()" style="flex: 1;">
                        👤 Yes, Search Member
                    </button>
                    <button class="btn btn-secondary" onclick="showGuestFormModal()" style="flex: 1;">
                        👥 No, Guest Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Search Modal -->
    <div id="memberSearchModal" class="modal">
        <div class="modal-content modal-medium">
            <div class="modal-header">
                <h2>🔍 Search Member</h2>
                <span class="modal-close" onclick="closeMemberSearchModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-box" style="margin-bottom: 20px;">
                    <input type="text" id="memberSearchInput" placeholder="🔍 Search by name, email, or phone..."
                           oninput="searchMembers()" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div id="memberSearchResults" style="max-height: 400px; overflow-y: auto;">
                    <p style="text-align: center; color: #64748b;">Type to search customers...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Form Modal -->
    <div id="guestFormModal" class="modal">
        <div class="modal-content modal-medium">
            <div class="modal-header">
                <h2>👥 Guest Customer Info</h2>
                <span class="modal-close" onclick="closeGuestFormModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="guestForm" onsubmit="submitGuestInfo(event)">
                    <div class="form-group">
                        <label>Full Name <span style="color: red;">*</span></label>
                        <input type="text" id="guestName" required placeholder="Enter full name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span style="color: red;">*</span></label>
                        <input type="tel" id="guestPhone" required placeholder="08xxxxxxxxxx" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email (Optional)</label>
                        <input type="email" id="guestEmail" placeholder="email@example.com" class="form-control">
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeGuestFormModal()" style="flex: 1;">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            Continue to Order Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Order Form Modal -->
    <div id="createOrderModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>📝 Create New Order</h2>
                <span class="modal-close" onclick="closeCreateOrderModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createOrderForm" onsubmit="submitCreateOrder(event)">
                    <input type="hidden" id="orderCustomerId">
                    <input type="hidden" id="orderIsMember">
                    <input type="hidden" id="orderGuestName">
                    <input type="hidden" id="orderGuestPhone">
                    <input type="hidden" id="orderGuestEmail">

                    <div class="customer-info-display" id="customerInfoDisplay" style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <!-- Customer info will be displayed here -->
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Service Type <span style="color: red;">*</span></label>
                            <select id="serviceType" required class="form-control">
                                <option value="">Select Service Type</option>
                                <option value="Repair">Repair</option>
                                <option value="Installation">Installation</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Data Recovery">Data Recovery</option>
                                <option value="Upgrade">Upgrade</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Device Type <span style="color: red;">*</span></label>
                            <input type="text" id="deviceType" required placeholder="e.g., Laptop, Desktop, Phone" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Brand</label>
                            <input type="text" id="deviceBrand" placeholder="e.g., Asus, HP, Dell" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" id="deviceModel" placeholder="e.g., ROG Strix, Pavilion" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Serial Number (Optional)</label>
                        <input type="text" id="serialNumber" placeholder="Device serial number" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Problem Description <span style="color: red;">*</span></label>
                        <textarea id="problemDescription" required rows="4" placeholder="Describe the problem..." class="form-control"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Location <span style="color: red;">*</span></label>
                            <select id="orderLocation" required class="form-control">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>">
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select id="orderPriority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 25px;">
                        <button type="button" class="btn btn-secondary" onclick="closeCreateOrderModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            ✅ Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Order Detail Modal -->
    <div id="editOrderModal" class="modal">
        <div class="modal-content modal-xlarge">
            <div class="modal-header">
                <h2>🔧 Edit Order - <span id="editOrderNumber"></span></h2>
                <span class="modal-close" onclick="closeEditOrderModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editOrderId">

                <!-- Order Info -->
                <div class="order-info-section" style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div id="orderInfoDisplay"></div>
                </div>

                <!-- Warranty Section -->
                <div style="background: #f0f9ff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-size: 16px; font-weight: 500; color: #1e293b;">
                        <input type="checkbox" id="warrantyCheckbox" style="width: 20px; height: 20px; margin-right: 12px; cursor: pointer;" onchange="updateWarrantyStatus()">
                        <span>✅ Warranty</span>
                    </label>
                    <p style="margin: 8px 0 0 32px; font-size: 13px; color: #64748b;">Check this if the order has warranty coverage</p>
                </div>

                <!-- Service Cost & Discount -->
                <div class="cost-section">
                    <h3>💰 Service Cost & Discount</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Service Cost (Rp)</label>
                            <input type="number" id="serviceCostInput" class="form-control" min="0" step="1000" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Discount / Potongan (Rp)</label>
                            <input type="number" id="discountInput" class="form-control" min="0" step="1000" placeholder="0">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="updateServiceCost()" style="margin-top: 10px;">
                        Update Service Cost & Discount
                    </button>
                </div>

                <!-- Spareparts Section -->
                <div class="spareparts-section" style="margin-top: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>🔧 Spareparts Used</h3>
                        <button class="btn btn-primary btn-sm" onclick="showAddSparepartModal()">
                            + Add Sparepart
                        </button>
                    </div>
                    <table class="data-table" id="sparepartsTable">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Name</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sparepartsTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b;">No spareparts added yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Costs Section -->
                <div class="custom-costs-section" style="margin-top: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>📋 Custom Costs</h3>
                        <button class="btn btn-primary btn-sm" onclick="showAddCustomCostModal()">
                            + Add Custom Cost
                        </button>
                    </div>
                    <table class="data-table" id="customCostsTable">
                        <thead>
                            <tr>
                                <th>Cost Name</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customCostsTableBody">
                            <tr>
                                <td colspan="4" style="text-align: center; color: #64748b;">No custom costs added yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Summary -->
                <div class="total-summary" style="background: #1e40af; color: white; padding: 20px; border-radius: 8px; margin-top: 30px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Service Cost:</span>
                        <span id="summaryServiceCost">Rp 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Spareparts Total:</span>
                        <span id="summarySpareparts">Rp 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Custom Costs Total:</span>
                        <span id="summaryCustomCosts">Rp 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 2px solid rgba(255,255,255,0.3); color: #fbbf24;">
                        <span>Discount / Potongan:</span>
                        <span id="summaryDiscount">- Rp 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 24px; font-weight: bold;">
                        <span>TOTAL:</span>
                        <span id="summaryTotal">Rp 0</span>
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button class="btn btn-secondary" onclick="closeEditOrderModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Sparepart Modal -->
    <div id="addSparepartModal" class="modal">
        <div class="modal-content modal-medium">
            <div class="modal-header">
                <h2>🔧 Add Sparepart</h2>
                <span class="modal-close" onclick="closeAddSparepartModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-box" style="margin-bottom: 20px;">
                    <input type="text" id="sparepartSearchInput" placeholder="🔍 Search sparepart..."
                           oninput="searchSpareparts()" class="form-control">
                </div>
                <div id="sparepartSearchResults" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <p style="text-align: center; color: #64748b;">Type to search inventory items...</p>
                </div>
                <div id="selectedSparepartInfo" style="display: none; background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Selected sparepart info will appear here -->
                </div>
                <form id="addSparepartForm" onsubmit="submitAddSparepart(event)" style="display: none;">
                    <input type="hidden" id="selectedItemId">
                    <div class="form-group">
                        <label>Quantity <span style="color: red;">*</span></label>
                        <input type="number" id="sparepartQuantity" required min="1" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea id="sparepartNotes" rows="2" class="form-control" placeholder="e.g., Replaced broken part"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddSparepartModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add to Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Custom Cost Modal -->
    <div id="addCustomCostModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>📋 Add Custom Cost</h2>
                <span class="modal-close" onclick="closeAddCustomCostModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addCustomCostForm" onsubmit="submitAddCustomCost(event)">
                    <div class="form-group">
                        <label>Cost Name <span style="color: red;">*</span></label>
                        <input type="text" id="customCostName" required placeholder="e.g., Transport Fee" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <input type="text" id="customCostDescription" placeholder="Brief description" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Amount (Rp) <span style="color: red;">*</span></label>
                        <input type="number" id="customCostAmount" required min="0" step="1000" placeholder="0" class="form-control">
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeAddCustomCostModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Add Cost</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/staff-orders.js"></script>
    <script src="../js/order-management.js"></script>
</body>
</html>
