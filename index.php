<?php
session_start();

// Check if staff viewing as customer
$viewing_as_customer = isset($_SESSION['viewing_as_customer']) && $_SESSION['viewing_as_customer'];
$is_staff = isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'staff' || $_SESSION['user_type'] == 'owner');

$is_logged_in = isset($_SESSION['user_logged_in']) ? $_SESSION['user_logged_in'] : false;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'customer';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plus Plus Komputer - Service Center & Penjualan Komputer Tarakan</title>
    <link rel="stylesheet" href="css/style.css">
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
                
                <?php if ($is_logged_in): ?>
                    <?php if ($viewing_as_customer && $is_staff): ?>
                        <li><a href="staff/dashboard.php" class="btn-staff-return">← Back to Staff Dashboard</a></li>
                    <?php endif; ?>
                    
                    <?php if ($user_type == 'customer' || $viewing_as_customer): ?>
                        <li><a href="order-history.php">Sejarah Order</a></li>
                    <?php endif; ?>
                    
                    <li><a href="#" class="user-profile">👤 <?php echo htmlspecialchars($user_name); ?></a></li>
                    <li><a href="logout.php" class="btn-logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                    <li><a href="register.php" class="btn-register">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

<!-- Hero Section -->
    <section id="home" class="hero">
        <video autoplay muted loop playsinline class="hero-video">
            <source src="images/background.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            <h1 class="hero-title">Professional Computer Service & Sales</h1>
            <p class="hero-subtitle">Melayani Penjualan LAPTOP/PC/Network/CCTV/Accessories<br>Berdiri Sejak 2007</p>
            <div class="hero-buttons">
                <a href="#track" class="btn btn-primary">Track Your Service</a>
                <a href="#services" class="btn btn-secondary">Our Services</a>
                <?php if ($is_logged_in): ?>
                    <a href="create-order.php" class="btn btn-special">Create a New Order</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="particles" id="particles"></div>
    </section>

<!-- Service Partners Gallery -->
    <section class="partners-gallery">
        <div class="container">
            <h2 class="section-title">Official Service Partners</h2>
            
            <!-- Navigation Dots -->
            <div class="gallery-nav">
                <button class="nav-dot active" data-slide="0">ASUS</button>
                <button class="nav-dot" data-slide="1">Lenovo</button>
                <button class="nav-dot" data-slide="2">Epson</button>
                <button class="nav-dot" data-slide="3">Canon</button>
                <button class="nav-dot" data-slide="4">Axioo</button>
                <button class="nav-dot" data-slide="5">MSI</button>
                <button class="nav-dot" data-slide="6">HP</button>
            </div>

            <!-- Gallery Slides -->
            <div class="gallery-container">
                <!-- ASUS -->
                <div class="gallery-slide active">
                    <img src="images/asus.jpg" alt="ASUS">
                    <div class="slide-content">
                        <h3>ASUS</h3>
                        <p>ASUS adalah pemimpin global dalam inovasi teknologi laptop gaming dan produktivitas. Sebagai service partner resmi ASUS, kami menyediakan layanan perbaikan dengan teknisi bersertifikat dan spare part original. Dari seri ROG Gaming hingga ZenBook premium, kami menangani semua lini produk ASUS dengan garansi resmi dan harga kompetitif.</p>
                    </div>
                </div>

                <!-- Lenovo -->
                <div class="gallery-slide">
                    <img src="images/lenovo.jpg" alt="Lenovo">
                    <div class="slide-content">
                        <h3>Lenovo</h3>
                        <p>Lenovo dikenal dengan laptop business dan ThinkPad yang legendaris. Sebagai service partner Lenovo, kami menawarkan solusi perbaikan cepat untuk semua seri Lenovo termasuk IdeaPad, Legion, dan ThinkPad. Dengan akses ke parts original dan dukungan teknis langsung dari Lenovo, kepuasan pelanggan adalah prioritas kami.</p>
                    </div>
                </div>

                <!-- Epson -->
                <div class="gallery-slide">
                    <img src="images/epson.jpg" alt="Epson">
                    <div class="slide-content">
                        <h3>Epson</h3>
                        <p>Epson adalah brand printer terkemuka dengan teknologi EcoTank yang revolusioner. Kami menyediakan service resmi untuk semua jenis printer Epson, dari L-Series hingga printer bisnis high-end. Dapatkan maintenance berkala, refill tinta original, dan perbaikan dengan garansi up to 3 tahun untuk biaya service dan 1 tahun untuk spare parts.</p>
                    </div>
                </div>

                <!-- Canon -->
                <div class="gallery-slide">
                    <img src="images/canon.jpg" alt="Canon">
                    <div class="slide-content">
                        <h3>Canon</h3>
                        <p>Canon terkenal dengan kualitas cetak yang superior untuk printer dan multifungsi. Service center kami melayani perbaikan printer Canon PIXMA, imageCLASS, dan seri profesional lainnya. Teknisi kami terlatih menangani berbagai masalah dari paper jam hingga print head cleaning dengan menggunakan tools dan parts resmi Canon.</p>
                    </div>
                </div>

                <!-- Axioo -->
                <div class="gallery-slide">
                    <img src="images/axioo.jpg" alt="Axioo">
                    <div class="slide-content">
                        <h3>Axioo</h3>
                        <p>Axioo adalah brand lokal Indonesia yang menawarkan laptop berkualitas dengan harga terjangkau. Sebagai authorized service partner, kami memberikan layanan perbaikan untuk semua produk Axioo termasuk MyBook dan Hype series. Nikmati kemudahan klaim garansi dan akses spare part original dengan harga kompetitif.</p>
                    </div>
                </div>

                <!-- MSI -->
                <div class="gallery-slide">
                    <img src="images/msi.webp" alt="MSI">
                    <div class="slide-content">
                        <h3>MSI (Micro-Star International)</h3>
                        <p>MSI adalah raja laptop gaming dengan performa extreme dan desain futuristik. Service center kami specialized dalam menangani laptop MSI gaming dan workstation dengan cooling system kompleks. Dari upgrade RAM dan SSD hingga repaste thermal dan cleaning deep, kami pastikan laptop gaming Anda tetap perform maksimal.</p>
                    </div>
                </div>

                <!-- HP -->
                <div class="gallery-slide">
                    <img src="images/hp.webp" alt="HP">
                    <div class="slide-content">
                        <h3>HP (Hewlett-Packard)</h3>
                        <p>HP adalah pioneer teknologi komputer sejak 1939 dengan reputasi global yang solid. Service partner HP kami melayani perbaikan untuk semua lini produk HP termasuk Pavilion, Envy, Omen Gaming, dan EliteBook business series. Dapatkan diagnostic gratis, garansi service, dan penanganan cepat untuk semua kebutuhan HP Anda.</p>
                    </div>
                </div>
            </div>

            <!-- Gallery Controls -->
            <div class="gallery-controls">
                <button class="gallery-btn prev">❮</button>
                <button class="gallery-btn next">❯</button>
            </div>
        </div>
    </section>

<!-- Features Section -->
    <section class="features">
        <div class="animated-arrow-right"></div>
        <div class="container">
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔧</div>
                    <h3>Expert Technicians</h3>
                    <p>Teknisi bersertifikat dengan pengalaman bertahun-tahun</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast Turnaround</h3>
                    <p>Layanan servis yang cepat dan efisien</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">✓</div>
                    <h3>Warranty Service</h3>
                    <p>Service partner resmi untuk berbagai merk ternama</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3>Multi-Brand Support</h3>
                    <p>ASUS, Lenovo, HP, Canon, Epson & banyak lagi</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Motto Section -->
    <section class="motto-section">
        <div class="floating-shapes"></div>
        <div class="container">
            <h2 class="motto-title">We Give You More</h2>
            <p class="motto-subtitle">Kualitas layanan terbaik, harga transparan, dan kepuasan pelanggan adalah prioritas kami</p>
        </div>
    </section>

    <!-- Service Tracking Widget -->
    <section id="track" class="tracking-section">
        <div class="animated-arrow-left"></div>
        <div class="container">
            <div class="tracking-card">
                <h2>Track Your Service Progress</h2>
                <p>Masukkan nomor servis Anda untuk mengecek status</p>
                <form action="track-service.php" method="GET" class="tracking-form">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="service_number" 
                            placeholder="Masukkan nomor servis (contoh: SRV-001234)" 
                            required
                        >
                        <button type="submit" class="btn btn-primary">Track Now</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Services Overview -->
    <section id="services" class="services-overview">
        <div class="container">
            <h2 class="section-title">Layanan Kami</h2>
            <div class="services-grid">
                <div class="service-item">
                    <h3>💻 Laptop Service</h3>
                    <p>Perbaikan laptop semua merk, ganti LCD, keyboard, upgrade RAM/SSD</p>
                </div>
                <div class="service-item">
                    <h3>🖥️ PC & All-in-One</h3>
                    <p>Service PC desktop, upgrade hardware, instalasi software</p>
                </div>
                <div class="service-item">
                    <h3>🖨️ Printer Service</h3>
                    <p>Service printer Canon, Epson, HP, Brother - maintenance & refill</p>
                </div>
                <div class="service-item">
                    <h3>📹 CCTV Installation</h3>
                    <p>Pemasangan dan maintenance CCTV untuk keamanan rumah/kantor</p>
                </div>
                <div class="service-item">
                    <h3>🌐 Networking</h3>
                    <p>Instalasi jaringan, WiFi setup, troubleshooting koneksi</p>
                </div>
                <div class="service-item">
                    <h3>🛒 Computer Sales</h3>
                    <p>Penjualan laptop, PC, accessories, dan peripheral komputer</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2 class="section-title">Tentang Kami</h2>
            <div class="about-content">
                <p>Plus Plus Komputer Tarakan adalah toko komputer terpercaya yang telah melayani masyarakat Tarakan sejak tahun 2007. Kami menyediakan berbagai layanan mulai dari penjualan laptop, PC, networking, CCTV, hingga accessories komputer.</p>
                <p>Sebagai service partner resmi dari berbagai brand ternama seperti ASUS, Lenovo, HP, Canon, Epson, dan lainnya, kami berkomitmen memberikan layanan terbaik dengan teknisi berpengalaman dan bergaransi.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <h2 class="section-title">Hubungi Kami</h2>
            <div class="contact-grid">
                <div class="contact-card">
                    <h3>📍 Lokasi Utama (Gusher)</h3>
                    <p>Ruko Gusher Block C-10</p>
                    <p>(pintu keluar Gusher/Ramayana)</p>
                    <p><strong>Telp:</strong> (0551) 36120</p>
                </div>
                <div class="contact-card">
                    <h3>📍 Show Room</h3>
                    <p>Jl. Kh. Dewantara RT.13</p>
                    <p>(depan Hotel Sejahtera) - Karang Balik</p>
                    <p><strong>Telp:</strong> (0551) 25256</p>
                </div>
                <div class="contact-card">
                    <h3>📱 Kontak Online</h3>
                    <p><strong>WhatsApp:</strong></p>
                    <p>+62 811 595 828</p>
                    <p>+62 811 5499 088</p>
                    <p>0812 5045 1642</p>
                    <p><strong>Email:</strong> plusplustrk@gmail.com</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>PLUS PLUS KOMPUTER</h3>
                    <p>Melayani Penjualan LAPTOP/PC/Network/CCTV/Accessories</p>
                    <p>Berdiri Sejak 2007</p>
                </div>
                <div class="footer-section">
                    <h4>Lokasi</h4>
                    <p><strong>Gusher:</strong> (0551) 36120</p>
                    <p><strong>Show Room:</strong> (0551) 25256</p>
                </div>
                <div class="footer-section">
                    <h4>Follow Us</h4>
                    <p>Facebook | Twitter | Instagram</p>
                    <p>@plusplustrk</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Plus Plus Komputer Tarakan. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>

    <?php if ($is_logged_in): ?>
    <!-- Floating Chat Button -->
    <div class="floating-chat-btn" id="chatBtn">
        <span class="chat-icon">💬</span>
        <span class="chat-badge">2</span>
    </div>

    <!-- Chat Widget -->
    <div class="chat-widget" id="chatWidget">
        <div class="chat-header">
            <div class="chat-header-info">
                <h3>Customer Support</h3>
                <span class="chat-status">● Online</span>
            </div>
            <button class="chat-close" id="chatClose">✕</button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-message received">
                <div class="message-avatar">👨‍💼</div>
                <div class="message-content">
                    <p>Halo! Ada yang bisa kami bantu?</p>
                    <span class="message-time">10:30</span>
                </div>
            </div>
            <div class="chat-message sent">
                <div class="message-content">
                    <p>Halo, saya ingin tanya status service saya</p>
                    <span class="message-time">10:31</span>
                </div>
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" placeholder="Type a message..." id="chatInput">
            <button class="chat-send" id="chatSend">➤</button>
        </div>
    </div>
    <?php endif; ?>

    <script src="js/script.js"></script>
    <script src="js/chat.js"></script>
</body>
</body>
</html>