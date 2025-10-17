<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Plus Plus Komputer</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop playsinline class="auth-video">
        <source src="images/background.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <!-- Back to Home Button -->
    <a href="index.php" class="back-home">← Back to Home</a>

    <div class="auth-container-center">
        <div class="auth-card-center">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="Plus Plus Komputer">
                </a>
            </div>

            <h1 class="main-title-center">REGISTER</h1>
            
            <div class="typing-container-center">
                <span class="typing-text" id="typingText"></span>
                <span class="cursor">_</span>
            </div>

            <form action="process-register.php" method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Nama Depan</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="John">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Nama Belakang</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Doe">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                    <span class="input-icon">📧</span>
                </div>

                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" required placeholder="08123456789">
                    <span class="input-icon">📱</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        👁️
                    </button>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        👁️
                    </button>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="terms" required>
                        <span class="checkmark"></span>
                        Saya setuju dengan syarat & ketentuan
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Daftar Sekarang</span>
                    <div class="btn-shine"></div>
                </button>
            </form>

            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>