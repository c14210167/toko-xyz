<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';
require_once '../config/init_permissions.php';

// Check if logged in and staff/owner
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

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

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build query
$where_conditions = ["user_type = 'customer'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($start_date)) {
    $where_conditions[] = "DATE(created_at) >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    $where_conditions[] = "DATE(created_at) <= :end_date";
    $params[':end_date'] = $end_date;
}

$where_clause = implode(' AND ', $where_conditions);

// Count total customers
$count_query = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_customers = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_customers / $per_page);

// Get customers with orders count
$query = "SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.address,
    u.created_at,
    COUNT(o.order_id) as total_orders,
    MAX(o.created_at) as last_order_date
FROM users u
LEFT JOIN orders o ON u.user_id = o.user_id
WHERE $where_clause
GROUP BY u.user_id
ORDER BY u.created_at DESC
LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <link rel="stylesheet" href="../css/customers.css">
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
            <a href="customers.php" class="nav-item active">
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
            <h1>Customers Management</h1>
            <div class="top-bar-info">
                <span class="customer-count">Total: <?php echo number_format($total_customers); ?> customers</span>
            </div>
        </div>

        <div class="content-area">
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="customers.php" class="filter-form">
                    <div class="filter-group">
                        <div class="search-box">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="search-input">
                            <button type="submit" class="search-btn">🔍</button>
                        </div>
                        
                        <div class="date-filters">
                            <div class="date-input-group">
                                <label>From:</label>
                                <input type="date" 
                                       name="start_date" 
                                       value="<?php echo htmlspecialchars($start_date); ?>"
                                       class="date-input">
                            </div>
                            <div class="date-input-group">
                                <label>To:</label>
                                <input type="date" 
                                       name="end_date" 
                                       value="<?php echo htmlspecialchars($end_date); ?>"
                                       class="date-input">
                            </div>
                            <button type="submit" class="filter-btn">Apply Filter</button>
                            <a href="customers.php" class="reset-btn">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Customer List -->
            <div class="customer-list">
                <?php if (empty($customers)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No customers found</h3>
                        <p>Try adjusting your search or filter criteria</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <div class="customer-card">
                            <div class="customer-avatar">
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                            </div>
                            <div class="customer-info">
                                <div class="customer-main">
                                    <h3 class="customer-name">
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                    </h3>
                                    <span class="customer-id">#<?php echo $customer['user_id']; ?></span>
                                </div>
                                <div class="customer-details">
                                    <div class="detail-item">
                                        <span class="detail-icon">📧</span>
                                        <span><?php echo htmlspecialchars($customer['email']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon">📱</span>
                                        <span><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-icon">📍</span>
                                        <span><?php echo htmlspecialchars($customer['address'] ?: 'No address'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="customer-stats">
                                <div class="stat-box">
                                    <div class="stat-value"><?php echo $customer['total_orders']; ?></div>
                                    <div class="stat-label">Orders</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value">
                                        <?php 
                                        if ($customer['last_order_date']) {
                                            $date = new DateTime($customer['last_order_date']);
                                            echo $date->format('d M Y');
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Last Order</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value">
                                        <?php 
                                        $created = new DateTime($customer['created_at']);
                                        echo $created->format('d M Y');
                                        ?>
                                    </div>
                                    <div class="stat-label">Joined</div>
                                </div>
                            </div>
                            <div class="customer-actions">
                                <button onclick="openCustomerModal(<?php echo $customer['user_id']; ?>)" class="action-btn view-btn">
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $query_string = http_build_query(array_filter([
                        'search' => $search,
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ]));
                    $query_string = $query_string ? '&' . $query_string : '';
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $query_string; ?>" class="page-btn">« First</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="page-btn">‹ Prev</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" 
                           class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="page-btn">Next ›</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>" class="page-btn">Last »</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Detail Modal -->
    <div id="customerModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalCustomerName">Customer Details</h2>
                <button class="modal-close" onclick="closeCustomerModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="loading-spinner">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        console.log('Script loaded'); // Debug log

        function openCustomerModal(customerId) {
            console.log('Opening modal for customer:', customerId);
            const modal = document.getElementById('customerModal');
            const modalContent = document.getElementById('modalContent');
            
            if (!modal) {
                console.error('Modal not found!');
                return;
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Show loading
            modalContent.innerHTML = '<div class="loading-spinner">Loading...</div>';
            
            // Load customer data via AJAX
            fetch(`get-customer-detail.php?id=${customerId}`)
                .then(response => {
                    console.log('Response received:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    if (data.success) {
                        displayCustomerDetail(data);
                    } else {
                        modalContent.innerHTML = '<p class="error">Failed to load customer data: ' + data.message + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = '<p class="error">Error loading data: ' + error.message + '</p>';
                });
        }

        function closeCustomerModal() {
            const modal = document.getElementById('customerModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function displayCustomerDetail(data) {
            const customer = data.customer;
            const orders = data.orders;
            
            document.getElementById('modalCustomerName').textContent = customer.full_name;
            
            let html = `
                <div class="modal-profile">
                    <div class="modal-info-grid">
                        <div class="info-item">
                            <span class="info-icon">📧</span>
                            <div>
                                <div class="info-label">Email</div>
                                <div class="info-value">${customer.email}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📱</span>
                            <div>
                                <div class="info-label">Phone</div>
                                <div class="info-value">${customer.phone || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">🔧</span>
                            <div>
                                <div class="info-label">Total Orders</div>
                                <div class="info-value">${customer.total_orders}</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">💰</span>
                            <div>
                                <div class="info-label">Total Revenue</div>
                                <div class="info-value">Rp ${formatNumber(customer.total_revenue)}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <h3 class="section-header">
                        <span>📋 Order History</span>
                        <span class="order-count">${orders.length} orders</span>
                    </h3>
            `;

            if (orders.length === 0) {
                html += '<p class="empty-message">No orders yet</p>';
            } else {
                const initialShow = 5;
                orders.forEach((order, index) => {
                    const statusConfig = getStatusConfig(order.status);
                    const isHidden = index >= initialShow ? 'style="display:none"' : '';
                    
                    html += `
                        <div class="order-item ${index >= initialShow ? 'order-hidden' : ''}" ${isHidden}>
                            <div class="order-header-compact">
                                <div>
                                    <strong class="order-num">${order.order_number}</strong>
                                    <span class="order-badge" style="background: ${statusConfig.color};">
                                        ${statusConfig.icon} ${statusConfig.label}
                                    </span>
                                </div>
                                <span class="order-time">${formatDate(order.created_at)}</span>
                            </div>
                            <div class="order-details-compact">
                                <div class="detail-compact">
                                    <strong>${order.brand} ${order.model}</strong> - ${order.device_type}
                                </div>
                                <div class="detail-compact">
                                    <span class="detail-icon">🔧</span> ${order.issue_type}
                                </div>
                                <div class="detail-compact">
                                    <span class="detail-icon">📍</span> ${order.location_name}
                                </div>
                                ${order.total_cost > 0 ? `
                                    <div class="detail-compact">
                                        <span class="detail-icon">💰</span> 
                                        <strong class="cost-highlight">Rp ${formatNumber(order.total_cost)}</strong>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });

                if (orders.length > initialShow) {
                    html += `
                        <button class="show-more-compact" onclick="toggleOrders(this)">
                            <span class="show-more-text">Show ${orders.length - initialShow} More Orders</span>
                            <span class="show-more-icon">▼</span>
                        </button>
                    `;
                }
            }

            html += '</div>';
            
            document.getElementById('modalContent').innerHTML = html;
        }

        function toggleOrders(button) {
            const hiddenOrders = document.querySelectorAll('.order-hidden');
            const isExpanded = button.classList.contains('expanded');
            
            hiddenOrders.forEach(order => {
                if (isExpanded) {
                    order.style.display = 'none';
                } else {
                    order.style.display = 'block';
                }
            });
            
            if (isExpanded) {
                button.classList.remove('expanded');
                button.querySelector('.show-more-text').textContent = `Show ${hiddenOrders.length} More Orders`;
                button.querySelector('.show-more-icon').textContent = '▼';
            } else {
                button.classList.add('expanded');
                button.querySelector('.show-more-text').textContent = 'Show Less';
                button.querySelector('.show-more-icon').textContent = '▲';
            }
        }

        function getStatusConfig(status) {
            const configs = {
                'pending': { label: 'Pending', color: '#f59e0b', icon: '⏳' },
                'in_progress': { label: 'In Progress', color: '#3b82f6', icon: '🔧' },
                'waiting_parts': { label: 'Waiting Parts', color: '#8b5cf6', icon: '📦' },
                'completed': { label: 'Completed', color: '#10b981', icon: '✅' },
                'cancelled': { label: 'Cancelled', color: '#ef4444', icon: '❌' },
                'ready_pickup': { label: 'Ready', color: '#06b6d4', icon: '🎉' }
            };
            return configs[status] || { label: 'Unknown', color: '#64748b', icon: '❓' };
        }

        function formatNumber(num) {
            return parseInt(num).toLocaleString('id-ID');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('customerModal');
            if (e.target === modal) {
                closeCustomerModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCustomerModal();
            }
        });
    </script>
</body>
</html>