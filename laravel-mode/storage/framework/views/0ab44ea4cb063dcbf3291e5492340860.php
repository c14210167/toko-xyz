<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> - Plus Plus Komputer</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/staff-dashboard.css')); ?>">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <div class="avatar">
                    <?php echo e(strtoupper(substr(auth()->user()->first_name, 0, 1))); ?>

                </div>
                <div class="status-indicator"></div>
            </div>
            <h3 class="user-name"><?php echo e(auth()->user()->first_name); ?> <?php echo e(auth()->user()->last_name); ?></h3>
            <p class="user-role"><?php echo e($primary_role ?? 'Staff'); ?></p>

            <?php if(isset($motivational_quotes)): ?>
            <!-- Motivational Quote Animation -->
            <div class="motivation-container">
                <span class="motivation-text" id="motivationText"></span>
                <span class="motivation-cursor">_</span>
            </div>
            <?php endif; ?>
        </div>

        <nav class="sidebar-nav">
            <a href="<?php echo e(route('staff.dashboard')); ?>" class="nav-item <?php echo e(request()->routeIs('staff.dashboard') ? 'active' : ''); ?>">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="<?php echo e(route('staff.orders.index')); ?>" class="nav-item <?php echo e(request()->routeIs('staff.orders.*') ? 'active' : ''); ?>">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="<?php echo e(route('staff.customers.index')); ?>" class="nav-item <?php echo e(request()->routeIs('staff.customers.*') ? 'active' : ''); ?>">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
            <a href="<?php echo e(route('staff.inventory.index')); ?>" class="nav-item <?php echo e(request()->routeIs('staff.inventory.*') ? 'active' : ''); ?>">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Inventory</span>
            </a>
            <?php if(hasPermission('view_products')): ?>
            <a href="#" class="nav-item">
                <span class="nav-icon">🛍️</span>
                <span class="nav-text">Products</span>
            </a>
            <?php endif; ?>
            <?php if(hasPermission('view_sales')): ?>
            <a href="#" class="nav-item">
                <span class="nav-icon">💰</span>
                <span class="nav-text">Sales</span>
            </a>
            <?php endif; ?>
            <?php if(hasPermission('view_expenses')): ?>
            <a href="#" class="nav-item">
                <span class="nav-icon">💸</span>
                <span class="nav-text">Expenses</span>
            </a>
            <?php endif; ?>
            <?php if(hasPermission('view_reports')): ?>
            <a href="#" class="nav-item">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Reports</span>
            </a>
            <?php endif; ?>
            <?php if(hasPermission('manage_permissions')): ?>
            <a href="#" class="nav-item">
                <span class="nav-icon">🔐</span>
                <span class="nav-text">Permissions</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
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
                <h1 class="page-title"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
            </div>
            <div class="header-right">
                <div class="header-user">
                    <span><?php echo e(auth()->user()->email); ?></span>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <?php if(isset($motivational_quotes)): ?>
    <script>
        const quotes = <?php echo json_encode($motivational_quotes, 15, 512) ?>;
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
    <?php endif; ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\XAMPP\htdocs\frontendproject\laravel-mode\resources\views/layouts/staff.blade.php ENDPATH**/ ?>