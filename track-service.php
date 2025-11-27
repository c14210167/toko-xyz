<?php
session_start();
require_once 'config/database.php';

$service_number = isset($_GET['service_number']) ? trim($_GET['service_number']) : '';
$order = null;
$timeline = [];
$costs = null;

if ($service_number) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get order details
    $query = "SELECT o.*, 
              CONCAT(u.first_name, ' ', u.last_name) as customer_name,
              CONCAT(t.first_name, ' ', t.last_name) as technician_name,
              l.name as location_name
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              LEFT JOIN users t ON o.technician_id = t.user_id
              JOIN locations l ON o.location_id = l.location_id
              WHERE o.order_number = :order_number";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_number', $service_number);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get timeline
        $timeline_query = "SELECT * FROM order_timeline 
                          WHERE order_id = :order_id 
                          ORDER BY event_date ASC";
        $timeline_stmt = $conn->prepare($timeline_query);
        $timeline_stmt->bindParam(':order_id', $order['order_id']);
        $timeline_stmt->execute();
        $timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get costs
        $cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
        $cost_stmt = $conn->prepare($cost_query);
        $cost_stmt->bindParam(':order_id', $order['order_id']);
        $cost_stmt->execute();
        $costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Service - Plus Plus Komputer</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/track-service.css">
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
            </ul>
        </div>
    </nav>

    <!-- Animated Background -->
    <div class="track-bg-animation"></div>

    <div class="track-container">
        <div class="container">
            <?php if (!$service_number): ?>
                <!-- Search Form -->
                <div class="track-search-section">
                    <div class="search-icon-animation">🔍</div>
                    <h1>Track Your Service</h1>
                    <p>Masukkan nomor service untuk melihat progress</p>
                    
                    <form method="GET" class="search-form">
                        <div class="search-input-group">
                            <input 
                                type="text" 
                                name="service_number" 
                                placeholder="Contoh: SRV-001234"
                                required
                                pattern="SRV-[0-9]{6}"
                                title="Format: SRV-XXXXXX"
                            >
                            <button type="submit">
                                <span>Track</span>
                                <div class="btn-shine"></div>
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif ($order): ?>
                <!-- Order Found -->
                <div class="track-result-section">
                    <div class="result-header">
                        <button onclick="window.location.href='track-service.php'" class="btn-back">
                            ← Track Lainnya
                        </button>
                        <h2><?php echo htmlspecialchars($order['order_number']); ?></h2>
                        <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                            <?php echo getStatusText($order['status']); ?>
                        </span>
                    </div>

                    <div class="result-grid">
                        <!-- Device Info Card -->
                        <div class="info-card">
                            <h3>Informasi Perangkat</h3>
                            <div class="info-item">
                                <span class="label">Jenis:</span>
                                <span class="value"><?php echo ucfirst($order['device_type']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Brand:</span>
                                <span class="value"><?php echo htmlspecialchars($order['brand']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Model:</span>
                                <span class="value"><?php echo htmlspecialchars($order['model']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Kerusakan:</span>
                                <span class="value"><?php echo htmlspecialchars($order['issue_type']); ?></span>
                            </div>
                            <?php if ($order['serial_number']): ?>
                            <div class="info-item">
                                <span class="label">Serial Number:</span>
                                <span class="value"><?php echo htmlspecialchars($order['serial_number']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Service Info Card -->
                        <div class="info-card">
                            <h3>Informasi Service</h3>
                            <div class="info-item">
                                <span class="label">Tanggal Masuk:</span>
                                <span class="value"><?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Lokasi:</span>
                                <span class="value"><?php echo htmlspecialchars($order['location_name']); ?></span>
                            </div>
                            <?php if ($order['technician_name']): ?>
                            <div class="info-item">
                                <span class="label">Teknisi:</span>
                                <span class="value"><?php echo htmlspecialchars($order['technician_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($order['estimated_completion']): ?>
                            <div class="info-item">
                                <span class="label">Estimasi Selesai:</span>
                                <span class="value"><?php echo date('d M Y', strtotime($order['estimated_completion'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <span class="label">Garansi:</span>
                                <span class="value"><?php echo $order['warranty_status'] ? 'Ya' : 'Tidak'; ?></span>
                            </div>
                        </div>

                        <!-- Cost Card -->
                        <?php if ($costs): ?>
                        <div class="info-card cost-card">
                            <h3>Rincian Biaya</h3>
                            <div class="cost-breakdown">
                                <div class="cost-item">
                                    <span>Spare Parts</span>
                                    <span><?php echo formatRupiah($costs['parts_cost'] ?? 0); ?></span>
                                </div>
                                <div class="cost-item">
                                    <span>Servis & Perbaikan</span>
                                    <span><?php echo formatRupiah($costs['service_cost'] ?? 0); ?></span>
                                </div>
                                <div class="cost-item">
                                    <span>Lainnya</span>
                                    <span><?php echo formatRupiah($costs['other_costs'] ?? 0); ?></span>
                                </div>
                                <div class="cost-total">
                                    <span><strong>Total</strong></span>
                                    <span><strong><?php echo formatRupiah($costs['total_cost'] ?? 0); ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Timeline Card -->
                        <div class="info-card timeline-card">
                            <h3>Timeline Progress</h3>
                            <div class="timeline">
                                <?php foreach ($timeline as $item): ?>
                                <div class="timeline-item <?php echo $item['status']; ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h4><?php echo htmlspecialchars($item['event_name']); ?></h4>
                                        <p><?php echo date('d M Y H:i', strtotime($item['event_date'])); ?></p>
                                        <?php if ($item['notes']): ?>
                                        <p class="timeline-notes"><?php echo htmlspecialchars($item['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['additional_notes']): ?>
                    <div class="notes-section">
                        <h3>Catatan Tambahan</h3>
                        <p><?php echo nl2br(htmlspecialchars($order['additional_notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Order Not Found -->
                <div class="track-not-found">
                    <div class="not-found-animation">
                        <div class="search-icon-large">🔍</div>
                        <div class="question-mark">?</div>
                    </div>
                    <h2>Service Tidak Ditemukan</h2>
                    <p>Nomor service <strong><?php echo htmlspecialchars($service_number); ?></strong> tidak ditemukan dalam sistem kami.</p>
                    
                    <div class="not-found-actions">
                        <button onclick="window.location.href='track-service.php'" class="btn-primary">
                            ← Coba Lagi
                        </button>
                        <button onclick="window.location.href='index.php'" class="btn-secondary">
                            Kembali ke Home
                        </button>
                    </div>

                    <div class="help-text">
                        <p>Pastikan Anda memasukkan nomor service dengan format yang benar (SRV-XXXXXX)</p>
                        <p>Jika masalah berlanjut, hubungi customer service kami</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/track-service.js"></script>
</body>
</html>