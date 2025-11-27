<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plus Plus Komputer - Service Center & Penjualan Komputer Tarakan</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <div class="logo">
            <a href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Plus Plus Komputer">
            </a>
        </div>
        <ul class="nav-menu">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#about">Tentang Kami</a></li>
            <li><a href="#contact">Kontak</a></li>

            @auth
                @if(auth()->user()->user_type == 'customer')
                    <li><a href="{{ route('order.history') }}">Sejarah Order</a></li>
                @endif

                <li><a href="#" class="user-profile">👤 {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</a></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn-logout" style="background: none; border: none; cursor: pointer; color: inherit; font: inherit;">Logout</button>
                    </form>
                </li>
            @else
                <li><a href="{{ route('login') }}" class="btn-login">Login</a></li>
                <li><a href="{{ route('register') }}" class="btn-register">Daftar</a></li>
            @endauth
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<section id="home" class="hero">
    <video autoplay muted loop playsinline class="hero-video">
        <source src="{{ asset('images/background.mp4') }}" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1 class="hero-title">Professional Computer Service & Sales</h1>
        <p class="hero-subtitle">Melayani Penjualan LAPTOP/PC/Network/CCTV/Accessories<br>Berdiri Sejak 2007</p>
        <div class="hero-buttons">
            <a href="#track" class="btn btn-primary">Track Your Service</a>
            <a href="#services" class="btn btn-secondary">Our Services</a>
            @auth
                <a href="{{ route('order.create') }}" class="btn btn-special">Create a New Order</a>
            @endauth
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
                <img src="{{ asset('images/asus.jpg') }}" alt="ASUS">
                <div class="slide-content">
                    <h3>ASUS</h3>
                    <p>ASUS adalah pemimpin global dalam inovasi teknologi laptop gaming dan produktivitas. Sebagai service partner resmi ASUS, kami menyediakan layanan perbaikan dengan teknisi bersertifikat dan spare part original.</p>
                </div>
            </div>

            <!-- Lenovo -->
            <div class="gallery-slide">
                <img src="{{ asset('images/lenovo.jpg') }}" alt="Lenovo">
                <div class="slide-content">
                    <h3>Lenovo</h3>
                    <p>Lenovo merupakan brand terkemuka untuk laptop bisnis dan personal computing. Kami siap melayani perbaikan dan maintenance laptop Lenovo ThinkPad, IdeaPad, dan Legion dengan jaminan kualitas terbaik.</p>
                </div>
            </div>

            <!-- Epson -->
            <div class="gallery-slide">
                <img src="{{ asset('images/epson.jpg') }}" alt="Epson">
                <div class="slide-content">
                    <h3>Epson</h3>
                    <p>Sebagai service partner Epson, kami menyediakan layanan maintenance dan perbaikan printer Epson. Dari printer rumahan hingga printer bisnis, kami siap membantu dengan teknisi berpengalaman.</p>
                </div>
            </div>

            <!-- Canon -->
            <div class="gallery-slide">
                <img src="{{ asset('images/canon.jpg') }}" alt="Canon">
                <div class="slide-content">
                    <h3>Canon</h3>
                    <p>Canon adalah brand terpercaya untuk printer dan scanner berkualitas tinggi. Kami melayani service dan maintenance printer Canon dengan spare part original dan garansi resmi.</p>
                </div>
            </div>

            <!-- Axioo -->
            <div class="gallery-slide">
                <img src="{{ asset('images/axioo.jpg') }}" alt="Axioo">
                <div class="slide-content">
                    <h3>Axioo</h3>
                    <p>Axioo adalah brand laptop lokal Indonesia yang berkualitas. Sebagai service partner resmi, kami siap melayani perbaikan laptop Axioo dengan teknisi tersertifikasi dan spare part original.</p>
                </div>
            </div>

            <!-- MSI -->
            <div class="gallery-slide">
                <img src="{{ asset('images/msi.webp') }}" alt="MSI">
                <div class="slide-content">
                    <h3>MSI</h3>
                    <p>MSI merupakan brand premium untuk laptop gaming dan workstation. Kami menyediakan layanan service untuk laptop MSI dengan penanganan khusus untuk komponen gaming high-end.</p>
                </div>
            </div>

            <!-- HP -->
            <div class="gallery-slide">
                <img src="{{ asset('images/hp.webp') }}" alt="HP">
                <div class="slide-content">
                    <h3>HP (Hewlett Packard)</h3>
                    <p>HP adalah salah satu brand komputer terbesar di dunia. Kami melayani service laptop dan printer HP dengan teknisi bersertifikat, spare part original, dan garansi resmi dari HP.</p>
                </div>
            </div>
        </div>

        <!-- Gallery Controls -->
        <div class="gallery-controls">
            <button class="gallery-btn prev">‹</button>
            <button class="gallery-btn next">›</button>
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
            <form action="#" method="GET" class="tracking-form">
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

<script src="{{ asset('js/script.js') }}"></script>

@auth
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

<script src="{{ asset('js/chat.js') }}"></script>
@endauth

</body>
</html>
