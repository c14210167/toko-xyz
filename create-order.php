<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: login.php');
    exit();
}

// Customer only - staff tidak bisa create order via halaman customer
if ($_SESSION['user_type'] != 'customer') {
    header('Location: staff/dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Order - Plus Plus Komputer</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/create-order.css">
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
                <li><a href="order-history.php">Sejarah Order</a></li>
                <li><a href="#" class="user-profile">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></a></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Animated Background Particles -->
    <div class="particles-bg"></div>

    <div class="create-order-container">
        <div class="container">
            <div class="order-header">
                <h1>Create New Service Order</h1>
                <p>Pilih jenis perangkat yang ingin di-service</p>
            </div>

            <!-- Step 1: Device Selection -->
            <div class="step-container active" id="step1">
                <div class="device-grid">
                    <div class="device-card" data-device="laptop">
                        <div class="device-icon">💻</div>
                        <h3>Laptop</h3>
                        <p>Service & perbaikan laptop</p>
                    </div>
                    <div class="device-card" data-device="printer">
                        <div class="device-icon">🖨️</div>
                        <h3>Printer</h3>
                        <p>Service printer & scanner</p>
                    </div>
                    <div class="device-card" data-device="computer">
                        <div class="device-icon">🖥️</div>
                        <h3>Komputer</h3>
                        <p>Service PC & All-in-One</p>
                    </div>
                    <div class="device-card" data-device="other">
                        <div class="device-icon">🔧</div>
                        <h3>Lainnya</h3>
                        <p>Perangkat lain</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Issue Selection -->
            <div class="step-container" id="step2">
                <button class="btn-back" onclick="goToStep(1)">← Kembali</button>
                <h2 class="step-title">Pilih jenis kerusakan</h2>
                <div class="issue-grid" id="issueGrid">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Step 3: Additional Details -->
            <div class="step-container" id="step3">
                <button class="btn-back" onclick="goToStep(2)">← Kembali</button>
                <h2 class="step-title">Detail Tambahan</h2>
                <div class="form-container">
                    <form id="orderForm" action="process-create-order.php" method="POST" onsubmit="return validateForm();">
                        <!-- Hidden inputs untuk device_type dan issue_type akan ditambahkan oleh JS -->
                        
                        <div class="form-group">
                            <label>Perangkat</label>
                            <input type="text" id="deviceType" name="device_display" readonly>
                        </div>
                        <div class="form-group">
                            <label>Kerusakan</label>
                            <input type="text" id="issueType" name="issue_display" readonly>
                        </div>
                        <div class="form-group">
                            <label>Merk / Brand</label>
                            <input type="text" id="brand" name="brand" placeholder="Contoh: ASUS, Lenovo, HP" required>
                        </div>
                        <div class="form-group">
                            <label>Model / Tipe</label>
                            <input type="text" id="model" name="model" placeholder="Contoh: ROG Strix G15" required>
                        </div>
                        <div class="form-group">
                            <label>Serial Number (jika ada)</label>
                            <input type="text" id="serialNumber" name="serial_number" placeholder="Opsional">
                        </div>
                        <div class="form-group">
                            <label>Apakah ada yang ingin ditambahkan?</label>
                            <textarea id="additionalNotes" name="additional_notes" rows="4" placeholder="Jelaskan detail tambahan atau kerusakan lainnya..."></textarea>
                        </div>
                        <button type="submit" class="btn-submit-order">
                            <span>Submit Order</span>
                            <div class="btn-shine"></div>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Step 4: Custom Issue (for "Lainnya") -->
            <div class="step-container" id="stepCustom">
                <button class="btn-back" onclick="goToStep(2)">← Kembali</button>
                <h2 class="step-title">Jelaskan Kerusakan</h2>
                <div class="form-container">
                    <div class="form-group">
                        <label>Jelaskan kerusakan secara detail</label>
                        <textarea id="customIssue" rows="6" placeholder="Tuliskan kerusakan yang dialami..." required></textarea>
                    </div>
                    <button class="btn-continue" onclick="proceedFromCustomIssue()">Lanjutkan →</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/create-order.js"></script>
</body>
</html>