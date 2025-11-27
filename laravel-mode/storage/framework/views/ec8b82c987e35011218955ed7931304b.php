<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Login - Plus Plus Komputer</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/auth.css')); ?>">
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop playsinline class="auth-video">
        <source src="<?php echo e(asset('images/background.mp4')); ?>" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <!-- Back to Home Button -->
    <a href="<?php echo e(route('home')); ?>" class="back-home">← Back to Home</a>

    <div class="auth-container-center">
        <div class="auth-card-center">
            <div class="logo">
                <a href="<?php echo e(route('home')); ?>">
                    <img src="<?php echo e(asset('images/logo.png')); ?>" alt="Plus Plus Komputer">
                </a>
            </div>
            <h1 class="main-title-center">LOGIN</h1>

            <div class="typing-container-center">
                <span class="typing-text" id="typingText"></span>
                <span class="cursor">_</span>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-error">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('login.post')); ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com" value="<?php echo e(old('email')); ?>">
                    <span class="input-icon">📧</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        👁️
                    </button>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="#" class="forgot-password">Lupa password?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Login</span>
                    <div class="btn-shine"></div>
                </button>
            </form>

            <div class="auth-footer">
                <p>Belum punya akun? <a href="<?php echo e(route('register')); ?>">Daftar sekarang</a></p>
            </div>
        </div>
    </div>

    <script src="<?php echo e(asset('js/auth.js')); ?>"></script>
</body>
</html>
<?php /**PATH D:\XAMPP\htdocs\frontendproject\laravel-mode\resources\views/auth/login.blade.php ENDPATH**/ ?>