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

// Get real-time stats from database
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'in_progress', 'waiting_parts')) as active_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'completed' AND DATE(updated_at) = CURDATE()) as completed_today,
    (SELECT COUNT(*) FROM orders WHERE status = 'waiting_parts') as pending_parts,
    (SELECT COALESCE(SUM(oc.total_cost), 0) FROM orders o 
     LEFT JOIN order_costs oc ON o.order_id = oc.order_id 
     WHERE o.status = 'completed' AND DATE(o.updated_at) = CURDATE()) as revenue_today";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get branch performance data
$branch_query = "SELECT 
    l.location_id,
    l.name as location_name,
    COUNT(o.order_id) as total_orders,
    COALESCE(SUM(oc.total_cost), 0) as total_revenue
FROM locations l
LEFT JOIN orders o ON l.location_id = o.location_id AND o.status = 'completed'
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
WHERE MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())
GROUP BY l.location_id, l.name";
$branch_stmt = $conn->prepare($branch_query);
$branch_stmt->execute();
$branches = $branch_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get P/L data (this month)
$pl_query = "SELECT 
    (SELECT COALESCE(SUM(oc.total_cost), 0) FROM orders o 
     LEFT JOIN order_costs oc ON o.order_id = oc.order_id 
     WHERE o.status = 'completed' 
     AND MONTH(o.created_at) = MONTH(CURDATE()) 
     AND YEAR(o.created_at) = YEAR(CURDATE())) as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses 
     WHERE MONTH(expense_date) = MONTH(CURDATE()) 
     AND YEAR(expense_date) = YEAR(CURDATE())) as total_expenses";
$pl_stmt = $conn->prepare($pl_query);
$pl_stmt->execute();
$pl = $pl_stmt->fetch(PDO::FETCH_ASSOC);

$net_profit = $pl['total_revenue'] - $pl['total_expenses'];

// Motivational quotes
$motivational_quotes = [
    'Keep pushing forward!',
    'You are doing great!',
    'Excellence is a habit',
    'Make today count',
    'Stay focused, stay strong'
];

// Get recent activities
$activity_query = "SELECT 
    o.order_number,
    o.status,
    o.created_at,
    o.updated_at,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name
FROM orders o
JOIN users u ON o.user_id = u.user_id
ORDER BY o.updated_at DESC
LIMIT 5";
$activity_stmt = $conn->prepare($activity_query);
$activity_stmt->execute();
$activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../css/staff-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="dashboard.php" class="nav-item active">
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
            <?php if (hasPermission('view_products')): ?>
            <a href="products.php" class="nav-item">
                <span class="nav-icon">🛍️</span>
                <span class="nav-text">Products</span>
            </a>
            <?php endif; ?>
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
            <h1>Dashboard</h1>
            <div class="top-bar-actions">
                <div class="notification-badge">
                    <span class="badge-icon">🔔</span>
                    <span class="badge-count">3</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Compact Stats - Horizontal -->
            <div class="compact-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['active_orders']; ?></div>
                    <div class="stat-label">Active Orders</div>
                </div>
                <div class="stat-item success">
                    <div class="stat-value"><?php echo $stats['completed_today']; ?></div>
                    <div class="stat-label">Completed Today</div>
                </div>
                <div class="stat-item warning">
                    <div class="stat-value"><?php echo $stats['pending_parts']; ?></div>
                    <div class="stat-label">Pending Parts</div>
                </div>
                <div class="stat-item info">
                    <div class="stat-value">Rp <?php echo number_format($stats['revenue_today'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Revenue Today</div>
                </div>
            </div>

            <!-- Revenue Chart with Advanced Filters -->
            <div class="chart-section">
                <div class="chart-controls">
                    <h2>📊 Revenue Analytics</h2>
                    <div class="filters">
                        <select id="periodFilter" class="filter-select">
                            <option value="1">1 Day</option>
                            <option value="3">3 Days</option>
                            <option value="7">1 Week</option>
                            <option value="14">2 Weeks</option>
                            <option value="30" selected>1 Month</option>
                            <option value="90">3 Months</option>
                            <option value="180">6 Months</option>
                            <option value="365">1 Year</option>
                            <option value="all">Overall</option>
                        </select>
                        <select id="typeFilter" class="filter-select">
                            <option value="all" selected>All Revenue</option>
                            <option value="service">Service Only</option>
                            <option value="sales">Product Sales Only</option>
                        </select>
                        <select id="locationFilter" class="filter-select">
                            <option value="all" selected>All Locations</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['location_id']; ?>">
                                    <?php echo htmlspecialchars($branch['location_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Compact Branch & P/L Side by Side -->
            <div class="info-grid">
                <!-- Branch Performance -->
                <div class="info-card">
                    <h3>🏢 Branch Performance</h3>
                    <?php if (empty($branches)): ?>
                        <p style="color: #64748b; text-align: center; padding: 1rem;">No data available</p>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                        <div class="branch-row">
                            <div class="branch-name"><?php echo htmlspecialchars($branch['location_name']); ?></div>
                            <div class="branch-metrics">
                                <span class="metric revenue">Rp <?php echo number_format($branch['total_revenue'], 0, ',', '.'); ?></span>
                                <span class="metric orders"><?php echo $branch['total_orders']; ?> orders</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- P/L Summary -->
                <div class="info-card">
                    <h3>💰 P/L Summary (This Month)</h3>
                    <div class="pl-row">
                        <span class="pl-label">Total Revenue</span>
                        <span class="pl-value positive">Rp <?php echo number_format($pl['total_revenue'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="pl-row">
                        <span class="pl-label">Total Expenses</span>
                        <span class="pl-value negative">Rp <?php echo number_format($pl['total_expenses'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="pl-row highlight">
                        <span class="pl-label">Net Profit</span>
                        <span class="pl-value">Rp <?php echo number_format($net_profit, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity - Compact -->
            <div class="activity-compact">
                <h3>Recent Activity</h3>
                <div class="activity-items">
                    <?php if (empty($activities)): ?>
                        <p style="color: #64748b; text-align: center; padding: 1rem;">No recent activities</p>
                    <?php else: ?>
                        <?php foreach (array_slice($activities, 0, 3) as $activity): ?>
                        <div class="activity-mini">
                            <span class="act-icon">
                                <?php 
                                if ($activity['status'] == 'completed') echo '✅';
                                elseif ($activity['status'] == 'in_progress') echo '🔧';
                                else echo '📦';
                                ?>
                            </span>
                            <span class="act-text">
                                <strong><?php echo htmlspecialchars($activity['order_number']); ?></strong> 
                                - <?php echo ucfirst(str_replace('_', ' ', $activity['status'])); ?>
                            </span>
                            <span class="act-time">
                                <?php 
                                $time_diff = time() - strtotime($activity['updated_at']);
                                if ($time_diff < 3600) {
                                    echo floor($time_diff / 60) . 'm';
                                } elseif ($time_diff < 86400) {
                                    echo floor($time_diff / 3600) . 'h';
                                } else {
                                    echo floor($time_diff / 86400) . 'd';
                                }
                                ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const motivationalQuotes = <?php echo json_encode($motivational_quotes); ?>;
        
        let revenueChart;
        const ctx = document.getElementById('revenueChart').getContext('2d');

        // Initialize chart with dummy data
        function initChart(data) {
            if (revenueChart) {
                revenueChart.destroy();
            }
            
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.values,
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#06b6d4',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => 'Rp ' + context.parsed.y.toLocaleString('id-ID')
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => 'Rp ' + (value / 1000000).toFixed(1) + 'M'
                            }
                        }
                    }
                }
            });
        }

        // Fetch data based on filters
        async function fetchRevenueData() {
            const period = document.getElementById('periodFilter').value;
            const type = document.getElementById('typeFilter').value;
            const location = document.getElementById('locationFilter').value;
            
            try {
                const response = await fetch(`get-revenue-data.php?period=${period}&type=${type}&location=${location}`);
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                
                initChart(data);
            } catch (error) {
                console.error('Error fetching revenue data:', error);
                // Fallback to dummy data
                initChart({
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    values: [12500000, 15200000, 14800000, 18300000]
                });
            }
        }

        // Event listeners for filters
        document.getElementById('periodFilter').addEventListener('change', fetchRevenueData);
        document.getElementById('typeFilter').addEventListener('change', fetchRevenueData);
        document.getElementById('locationFilter').addEventListener('change', fetchRevenueData);

        // Initial load
        fetchRevenueData();

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

        setTimeout(typeMotivation, 1000);
    </script>
</body>
</html>