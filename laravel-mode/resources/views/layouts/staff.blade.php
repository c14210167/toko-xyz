<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Plus Plus Komputer</title>
    <link rel="stylesheet" href="{{ asset('css/staff-dashboard.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <div class="avatar">
                    {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                </div>
                <div class="status-indicator"></div>
            </div>
            <h3 class="user-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
            <p class="user-role">{{ $primary_role ?? 'Staff' }}</p>

            @if(isset($motivational_quotes))
            <!-- Motivational Quote Animation -->
            <div class="motivation-container">
                <span class="motivation-text" id="motivationText"></span>
                <span class="motivation-cursor">_</span>
            </div>
            @endif
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('staff.dashboard') }}" class="nav-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="{{ route('staff.orders.index') }}" class="nav-item {{ request()->routeIs('staff.orders.*') ? 'active' : '' }}">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="{{ route('staff.customers.index') }}" class="nav-item {{ request()->routeIs('staff.customers.*') ? 'active' : '' }}">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
            <a href="{{ route('staff.inventory.index') }}" class="nav-item {{ request()->routeIs('staff.inventory.*') ? 'active' : '' }}">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Inventory</span>
            </a>
            @if(hasPermission('view_products'))
            <a href="#" class="nav-item">
                <span class="nav-icon">🛍️</span>
                <span class="nav-text">Products</span>
            </a>
            @endif
            @if(hasPermission('view_sales'))
            <a href="#" class="nav-item">
                <span class="nav-icon">💰</span>
                <span class="nav-text">Sales</span>
            </a>
            @endif
            @if(hasPermission('view_expenses'))
            <a href="#" class="nav-item">
                <span class="nav-icon">💸</span>
                <span class="nav-text">Expenses</span>
            </a>
            @endif
            @if(hasPermission('view_reports'))
            <a href="#" class="nav-item">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Reports</span>
            </a>
            @endif
            @if(hasPermission('manage_permissions'))
            <a href="#" class="nav-item">
                <span class="nav-icon">🔐</span>
                <span class="nav-text">Permissions</span>
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-header">
            <div class="header-left">
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="header-right">
                <div class="header-user">
                    <span>{{ auth()->user()->email }}</span>
                </div>
            </div>
        </div>

        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @if(isset($motivational_quotes))
    <script>
        const quotes = @json($motivational_quotes);
        let currentQuote = 0;

        function typeQuote() {
            const element = document.getElementById('motivationText');
            if (!element) return;

            const quote = quotes[currentQuote];
            let i = 0;
            element.textContent = '';

            const typing = setInterval(() => {
                if (i < quote.length) {
                    element.textContent += quote.charAt(i);
                    i++;
                } else {
                    clearInterval(typing);
                    setTimeout(() => {
                        currentQuote = (currentQuote + 1) % quotes.length;
                        typeQuote();
                    }, 3000);
                }
            }, 100);
        }

        if (document.getElementById('motivationText')) {
            typeQuote();
        }
    </script>
    @endif

    @stack('scripts')
</body>
</html>
