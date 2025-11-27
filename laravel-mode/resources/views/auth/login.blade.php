<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Plus Plus Komputer</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop playsinline class="auth-video">
        <source src="{{ asset('images/background.mp4') }}" type="video/mp4">
    </video>
    <div class="video-overlay"></div>

    <!-- Back to Home Button -->
    <a href="{{ route('home') }}" class="back-home">← Back to Home</a>

    <div class="auth-container-center">
        <div class="auth-card-center">
            <div class="logo">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Plus Plus Komputer">
                </a>
            </div>
            <h1 class="main-title-center">LOGIN</h1>

            <div class="typing-container-center">
                <span class="typing-text" id="typingText"></span>
                <span class="cursor">_</span>
            </div>

            @if($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="auth-form">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com" value="{{ old('email') }}">
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
                <p>Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
