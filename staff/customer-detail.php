<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';

// Check if logged in and staff/owner
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['user_type'] == 'customer') {
    header('Location: ../index.php');
    exit();
}

$customer_id = $_GET['id'] ?? null;
if (!$customer_id) {
    header('Location: customers.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Get user roles
$roles = $permissionManager->getUserRoles();
$role_names = array_map(function($role) { return $role['role_name']; }, $roles);
$primary_role = !empty($role_names) ? $role_names[0] : 'Staff';

// Get customer info
$customer_query = "SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.address,
    u.created_at,
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oc.total_cost ELSE 0 END), 0) as total_revenue,
    MAX(o.created_at) as last_order_date
FROM users u
LEFT JOIN orders o ON u.user_id = o.user_id
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
WHERE u.user_id = :customer_id AND u.user_type = 'customer'
GROUP BY u.user_id";

$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bindParam(':customer_id', $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php?error=customer_not_found');
    exit();
}

// Get orders with details (newest first)
$orders_query = "SELECT 
    o.order_id,
    o.order_number,
    o.device_type,
    o.brand,
    o.model,
    o.issue_type,
    o.status,
    o.created_at,
    o.updated_at,
    l.name as location_name,
    COALESCE(oc.total_cost, 0) as total_cost,
    CONCAT(t.first_name, ' ', t.last_name) as technician_name
FROM orders o
LEFT JOIN locations l ON o.location_id = l.location_id
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
LEFT JOIN users t ON o.technician_id = t.user_id
WHERE o.user_id = :customer_id
ORDER BY o.created_at DESC";

$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bindParam(':customer_id', $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Status colors and labels
$status_config = [
    'pending' => ['label' => 'Pending', 'color' => '#f59e0b', 'icon' => '⏳'],
    'in_progress' => ['label' => 'In Progress', 'color' => '#3b82f6', 'icon' => '🔧'],
    'waiting_parts' => ['label' => 'Waiting Parts', 'color' => '#8b5cf6', 'icon' => '📦'],
    'completed' => ['label' => 'Completed', 'color' => '#10b981', 'icon' => '✅'],
    'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444', 'icon' => '❌'],
    'ready_pickup' => ['label' => 'Ready for Pickup', 'color' => '#06b6d4', 'icon' => '🎉']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Detail - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/customer-detail.css">
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
            <a href="orders.php" class="nav-item">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="customers.php" class="nav-item active">
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
            <div class="breadcrumb">
                <a href="customers.php">Customers</a>
                <span class="separator">›</span>
                <span class="current"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
            </div>
        </div>

        <div class="content-area">
            <!-- Customer Profile Section -->
            <div class="customer-profile-card">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h1>
                        <div class="profile-meta">
                            <span class="meta-item">
                                <span class="meta-icon">🆔</span>
                                Customer #<?php echo $customer['user_id']; ?>
                            </span>
                            <span class="meta-item">
                                <span class="meta-icon">📅</span>
                                Joined <?php echo date('d M Y', strtotime($customer['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="profile-stats">
                    <div class="stat-large">
                        <div class="stat-icon">🔧</div>
                        <div class="stat-content">
                            <div class="stat-value-large"><?php echo $customer['total_orders']; ?></div>
                            <div class="stat-label-large">Total Orders</div>
                        </div>
                    </div>
                    <div class="stat-large">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-value-large">Rp <?php echo number_format($customer['total_revenue'], 0, ',', '.'); ?></div>
                            <div class="stat-label-large">Total Revenue</div>
                        </div>
                    </div>
                    <div class="stat-large">
                        <div class="stat-icon">📆</div>
                        <div class="stat-content">
                            <div class="stat-value-large">
                                <?php 
                                if ($customer['last_order_date']) {
                                    echo date('d M Y', strtotime($customer['last_order_date']));
                                } else {
                                    echo 'No orders yet';
                                }
                                ?>
                            </div>
                            <div class="stat-label-large">Last Order</div>
                        </div>
                    </div>
                </div>

                <div class="profile-contact">
                    <h3 class="section-title">Contact Information</h3>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <span class="contact-icon">📧</span>
                            <div class="contact-info">
                                <div class="contact-label">Email</div>
                                <div class="contact-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                            </div>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon">📱</span>
                            <div class="contact-info">
                                <div class="contact-label">Phone</div>
                                <div class="contact-value"><?php echo htmlspecialchars($customer['phone'] ?: 'Not provided'); ?></div>
                            </div>
                        </div>
                        <div class="contact-item full-width">
                            <span class="contact-icon">📍</span>
                            <div class="contact-info">
                                <div class="contact-label">Address</div>
                                <div class="contact-value"><?php echo htmlspecialchars($customer['address'] ?: 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Timeline Section -->
            <div class="timeline-section">
                <div class="timeline-header">
                    <h2 class="timeline-title">📋 Order History</h2>
                    <span class="timeline-count"><?php echo count($orders); ?> orders</span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-timeline">
                        <div class="empty-icon">📦</div>
                        <h3>No orders yet</h3>
                        <p>This customer hasn't placed any orders</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-container">
                        <?php 
                        $initial_display = 5; // Show only 5 orders initially
                        foreach ($orders as $index => $order): 
                            $status = $status_config[$order['status']] ?? ['label' => 'Unknown', 'color' => '#64748b', 'icon' => '❓'];
                            $is_hidden = $index >= $initial_display;
                        ?>
                            <div class="timeline-item <?php echo $is_hidden ? 'timeline-hidden' : ''; ?>" data-index="<?php echo $index; ?>">
                                <div class="timeline-marker" style="background: <?php echo $status['color']; ?>;">
                                    <?php echo $status['icon']; ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-card">
                                        <div class="timeline-card-header">
                                            <div class="order-title">
                                                <h3 class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></h3>
                                                <span class="order-status" style="background: <?php echo $status['color']; ?>;">
                                                    <?php echo $status['label']; ?>
                                                </span>
                                            </div>
                                            <div class="order-date">
                                                <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?>
                                            </div>
                                        </div>

                                        <div class="timeline-card-body">
                                            <div class="order-device">
                                                <span class="device-icon">💻</span>
                                                <div class="device-info">
                                                    <strong><?php echo htmlspecialchars($order['brand'] . ' ' . $order['model']); ?></strong>
                                                    <span class="device-type"><?php echo htmlspecialchars($order['device_type']); ?></span>
                                                </div>
                                            </div>

                                            <div class="order-details">
                                                <div class="detail-row">
                                                    <span class="detail-label">Issue:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($order['issue_type']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <span class="detail-label">Location:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($order['location_name']); ?></span>
                                                </div>
                                                <?php if ($order['technician_name']): ?>
                                                <div class="detail-row">
                                                    <span class="detail-label">Technician:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($order['technician_name']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($order['status'] == 'completed' && $order['total_cost'] > 0): ?>
                                                <div class="detail-row">
                                                    <span class="detail-label">Total Cost:</span>
                                                    <span class="detail-value cost">Rp <?php echo number_format($order['total_cost'], 0, ',', '.'); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="timeline-card-footer">
                                            <span class="last-updated">
                                                Last updated: <?php echo date('d M Y, H:i', strtotime($order['updated_at'])); ?>
                                            </span>
                                            <a href="order-detail.php?order_number=<?php echo $order['order_number']; ?>" class="view-order-btn">
                                                View Details →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($orders) > $initial_display): ?>
                        <div class="show-more-container">
                            <button id="showMoreBtn" class="show-more-btn">
                                <span class="btn-text">Show More Orders</span>
                                <span class="btn-icon">▼</span>
                            </button>
                            <button id="showLessBtn" class="show-more-btn" style="display: none;">
                                <span class="btn-text">Show Less</span>
                                <span class="btn-icon">▲</span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showMoreBtn = document.getElementById('showMoreBtn');
            const showLessBtn = document.getElementById('showLessBtn');
            const hiddenItems = document.querySelectorAll('.timeline-hidden');

            if (showMoreBtn) {
                showMoreBtn.addEventListener('click', function() {
                    hiddenItems.forEach(item => {
                        item.classList.remove('timeline-hidden');
                        item.classList.add('timeline-visible');
                    });
                    showMoreBtn.style.display = 'none';
                    showLessBtn.style.display = 'flex';
                    
                    // Smooth scroll to first newly revealed item
                    setTimeout(() => {
                        const firstNewItem = document.querySelector('[data-index="5"]');
                        if (firstNewItem) {
                            firstNewItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }, 100);
                });
            }

            if (showLessBtn) {
                showLessBtn.addEventListener('click', function() {
                    hiddenItems.forEach(item => {
                        item.classList.remove('timeline-visible');
                        item.classList.add('timeline-hidden');
                    });
                    showLessBtn.style.display = 'none';
                    showMoreBtn.style.display = 'flex';
                    
                    // Scroll back to top of timeline
                    const timelineSection = document.querySelector('.timeline-section');
                    if (timelineSection) {
                        timelineSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        });
    </script>
</body>
</html>