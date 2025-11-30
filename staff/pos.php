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

// Check permission - staff need access_pos permission
$permissionManager->requireAnyPermission(['access_pos', 'create_transaction'], 'dashboard.php');

// Get user roles
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';

// Check if user has an open POS session
$check_session_query = "SELECT session_id, opened_at, opening_balance
                        FROM pos_sessions
                        WHERE user_id = :user_id AND status = 'open'
                        ORDER BY opened_at DESC LIMIT 1";
$check_stmt = $conn->prepare($check_session_query);
$check_stmt->bindParam(':user_id', $_SESSION['user_id']);
$check_stmt->execute();
$active_session = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's location (if any)
$user_location = null;
if (isset($_SESSION['location_id'])) {
    $loc_query = "SELECT location_name FROM locations WHERE location_id = :location_id";
    $loc_stmt = $conn->prepare($loc_query);
    $loc_stmt->bindParam(':location_id', $_SESSION['location_id']);
    $loc_stmt->execute();
    $user_location = $loc_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/pos.css">
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
            <a href="pos.php" class="nav-item active">
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
            <h1>Point of Sale</h1>
            <div class="session-info">
                <?php if ($active_session): ?>
                    <span class="session-status active">Session Active</span>
                    <span class="session-time">Started: <?php echo date('H:i', strtotime($active_session['opened_at'])); ?></span>
                <?php else: ?>
                    <span class="session-status inactive">No Active Session</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">
            <?php if (!$active_session): ?>
                <!-- Start Session View -->
                <div class="session-start-container">
                    <div class="session-start-card">
                        <div class="card-icon">💰</div>
                        <h2>Start POS Session</h2>
                        <p>Begin your cashier session to start processing sales</p>

                        <form id="startSessionForm" class="session-form">
                            <div class="form-group">
                                <label for="openingBalance">Opening Cash Balance</label>
                                <div class="currency-input">
                                    <span class="currency-symbol">Rp</span>
                                    <input type="number" id="openingBalance" step="1000" min="0" placeholder="0" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="sessionNotes">Notes (Optional)</label>
                                <textarea id="sessionNotes" rows="3" placeholder="Any notes about this session..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                Start Session
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active POS View -->
                <div class="pos-container">
                    <!-- Left Side: Product Selection -->
                    <div class="pos-left">
                        <div class="product-search">
                            <input type="text" id="productSearch" placeholder="🔍 Search products by name or SKU...">
                        </div>

                        <div class="product-grid" id="productGrid">
                            <div class="loading-spinner">Loading products...</div>
                        </div>
                    </div>

                    <!-- Right Side: Cart & Checkout -->
                    <div class="pos-right">
                        <div class="cart-header">
                            <h3>Current Sale</h3>
                            <button class="btn btn-sm btn-secondary" onclick="clearCart()">Clear</button>
                        </div>

                        <div class="cart-items" id="cartItems">
                            <div class="empty-cart">
                                <p>Cart is empty</p>
                                <small>Scan or select products to add</small>
                            </div>
                        </div>

                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="cartSubtotal">Rp 0</span>
                            </div>
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span id="cartDiscount">Rp 0</span>
                            </div>
                            <div class="summary-row summary-total">
                                <span>Total:</span>
                                <span id="cartTotal">Rp 0</span>
                            </div>
                        </div>

                        <div class="cart-actions">
                            <button class="btn btn-success btn-lg btn-block" onclick="showPaymentModal()" id="checkoutBtn" disabled>
                                Proceed to Payment
                            </button>
                            <button class="btn btn-danger btn-sm btn-block" onclick="showEndSessionModal()">
                                End Session
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Process Payment</h2>
                <button class="btn-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-total">
                    <span>Total Amount:</span>
                    <span class="amount" id="paymentTotal">Rp 0</span>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <div class="payment-methods">
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="cash" checked onchange="updatePaymentFields()">
                            <span>💵 Cash</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="card" onchange="updatePaymentFields()">
                            <span>💳 Card</span>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="qris" onchange="updatePaymentFields()">
                            <span>📱 QRIS</span>
                        </label>
                    </div>
                </div>

                <div id="cashPaymentFields">
                    <div class="form-group">
                        <label for="cashReceived">Cash Received</label>
                        <div class="currency-input">
                            <span class="currency-symbol">Rp</span>
                            <input type="number" id="cashReceived" step="1000" min="0" placeholder="0" oninput="calculateChange()">
                        </div>
                    </div>
                    <div class="change-display" id="changeDisplay">
                        <span>Change:</span>
                        <span class="amount">Rp 0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="customerSearch">Customer (Optional)</label>
                    <div class="customer-search-container">
                        <input type="text" id="customerSearch" placeholder="Search customer by name..." autocomplete="off">
                        <input type="hidden" id="customerId" value="">
                        <div class="customer-suggestions" id="customerSuggestions"></div>
                    </div>
                    <small style="color: #64748b;">Leave empty for walk-in customer</small>
                </div>

                <div class="form-group">
                    <label for="paymentNotes">Notes (Optional)</label>
                    <textarea id="paymentNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="processPayment()" id="completePaymentBtn">
                    Complete Sale
                </button>
            </div>
        </div>
    </div>

    <!-- End Session Modal -->
    <div id="endSessionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>End POS Session</h2>
                <button class="btn-close" onclick="closeEndSessionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="session-summary" id="sessionSummary">
                    <div class="summary-item">
                        <span>Opening Balance:</span>
                        <span id="summaryOpeningBalance">Rp 0</span>
                    </div>
                    <div class="summary-item">
                        <span>Total Transactions:</span>
                        <span id="summaryTransactions">0</span>
                    </div>
                    <div class="summary-item">
                        <span>Cash Sales:</span>
                        <span id="summaryCashSales">Rp 0</span>
                    </div>
                    <div class="summary-item">
                        <span>Card Sales:</span>
                        <span id="summaryCardSales">Rp 0</span>
                    </div>
                    <div class="summary-item">
                        <span>QRIS Sales:</span>
                        <span id="summaryQrisSales">Rp 0</span>
                    </div>
                    <div class="summary-item total">
                        <span>Expected Balance:</span>
                        <span id="summaryExpectedBalance">Rp 0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="closingBalance">Actual Closing Cash Balance</label>
                    <div class="currency-input">
                        <span class="currency-symbol">Rp</span>
                        <input type="number" id="closingBalance" step="1000" min="0" placeholder="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="closingNotes">Closing Notes (Optional)</label>
                    <textarea id="closingNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEndSessionModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="endSession()">
                    End Session
                </button>
            </div>
        </div>
    </div>

    <script>
        const sessionId = <?php echo $active_session ? $active_session['session_id'] : 'null'; ?>;
        const hasActiveSession = <?php echo $active_session ? 'true' : 'false'; ?>;
    </script>
    <script src="../js/pos.js"></script>
</body>
</html>
