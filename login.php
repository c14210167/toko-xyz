<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plus Plus Komputer</title>
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
            <h1 class="main-title-center">LOGIN</h1>
            
            <div class="typing-container-center">
                <span class="typing-text" id="typingText"></span>
                <span class="cursor">_</span>
            </div>

            <form action="process-login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                    <span class="input-icon">📧</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        👁️
                    </button>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Login</span>
                    <div class="btn-shine"></div>
                </button>
            </form>

            <div class="auth-footer">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            </div>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>