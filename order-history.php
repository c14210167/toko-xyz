<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get all orders untuk user ini
$query = "SELECT o.*, 
          l.name as location_name,
          CONCAT(t.first_name, ' ', t.last_name) as technician_name,
          oc.total_cost
          FROM orders o
          JOIN locations l ON o.location_id = l.location_id
          LEFT JOIN users t ON o.technician_id = t.user_id
          LEFT JOIN order_costs oc ON o.order_id = oc.order_id
          WHERE o.user_id = :user_id
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusClass($status) {
    $classes = [
        'pending' => 'status-waiting',
        'in_progress' => 'status-progress',
        'waiting_parts' => 'status-waiting',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    return $classes[$status] ?? 'status-waiting';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'waiting_parts' => 'Menunggu Spare Part',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];
    return $texts[$status] ?? $status;
}

function calculateProgress($status) {
    $progress = [
        'pending' => 20,
        'in_progress' => 60,
        'waiting_parts' => 40,
        'completed' => 100,
        'cancelled' => 0
    ];
    return $progress[$status] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sejarah Order - Plus Plus Komputer</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/order-history.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="Plus Plus Komputer">
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#services">Services</a></li>
                <li><a href="index.php#about">Tentang Kami</a></li>
                <li><a href="index.php#contact">Kontak</a></li>
                <li><a href="order-history.php" class="active">Sejarah Order</a></li>
                <li><a href="#" class="user-profile">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="order-history-container">
        <div class="container">
            <div class="page-header">
                <h1>Sejarah Order Service</h1>
                <p>Lihat semua riwayat service yang pernah Anda lakukan</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2>Belum Ada Order</h2>
                    <p>Anda belum memiliki riwayat service</p>
                    <a href="create-order.php" class="btn-create-order">Buat Order Baru</a>
                </div>
            <?php else: ?>
                <div class="filter-section">
                    <button class="filter-btn active" data-filter="all">Semua (<?php echo count($orders); ?>)</button>
                    <button class="filter-btn" data-filter="progress">Dalam Proses</button>
                    <button class="filter-btn" data-filter="completed">Selesai</button>
                    <button class="filter-btn" data-filter="waiting">Menunggu</button>
                </div>

                <div class="orders-grid">
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card" data-status="<?php echo $order['status']; ?>">
                        <div class="order-card-header">
                            <div class="order-id"><?php echo htmlspecialchars($order['order_number']); ?></div>
                            <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                                <?php echo getStatusText($order['status']); ?>
                            </span>
                        </div>
                        <div class="order-card-body">
                            <h3><?php echo ucfirst($order['device_type']); ?> <?php echo htmlspecialchars($order['brand']); ?></h3>
                            <p class="order-issue">🔧 <?php echo htmlspecialchars($order['issue_type']); ?></p>
                            <p class="order-date">📅 <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                            <p class="order-location">📍 <?php echo htmlspecialchars($order['location_name']); ?></p>
                            
                            <?php if ($order['total_cost']): ?>
                            <p class="order-cost">💰 Rp <?php echo number_format($order['total_cost'], 0, ',', '.'); ?></p>
                            <?php endif; ?>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo calculateProgress($order['status']); ?>%"></div>
                            </div>
                            <p class="progress-text"><?php echo calculateProgress($order['status']); ?>% Complete</p>
                        </div>
                        <div class="order-card-footer">
                            <button class="btn-detail" onclick="showOrderDetail('<?php echo $order['order_number']; ?>')">
                                Lihat Detail
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <button class="modal-close" id="modalClose">✕</button>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="js/order-history.js"></script>
</body>
</html>