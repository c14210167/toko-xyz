

<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<!-- Compact Stats - Horizontal -->
<div class="compact-stats">
    <div class="stat-item">
        <div class="stat-value"><?php echo e($stats['active_orders']); ?></div>
        <div class="stat-label">Active Orders</div>
    </div>
    <div class="stat-item success">
        <div class="stat-value"><?php echo e($stats['completed_today']); ?></div>
        <div class="stat-label">Completed Today</div>
    </div>
    <div class="stat-item warning">
        <div class="stat-value"><?php echo e($stats['pending_parts']); ?></div>
        <div class="stat-label">Pending Parts</div>
    </div>
    <div class="stat-item info">
        <div class="stat-value">Rp <?php echo e(number_format($stats['revenue_today'], 0, ',', '.')); ?></div>
        <div class="stat-label">Revenue Today</div>
    </div>
</div>

<!-- Revenue Chart with Advanced Filters -->
<div class="chart-section">
    <div class="chart-controls">
        <h2>📊 Revenue Analytics</h2>
        <div class="filters">
            <select id="periodFilter" class="filter-select">
                <option value="1">1 Day</option>
                <option value="3">3 Days</option>
                <option value="7">1 Week</option>
                <option value="14">2 Weeks</option>
                <option value="30" selected>1 Month</option>
                <option value="90">3 Months</option>
                <option value="180">6 Months</option>
                <option value="365">1 Year</option>
                <option value="all">Overall</option>
            </select>
            <select id="typeFilter" class="filter-select">
                <option value="all" selected>All Revenue</option>
                <option value="service">Service Only</option>
                <option value="sales">Product Sales Only</option>
            </select>
            <select id="locationFilter" class="filter-select">
                <option value="all" selected>All Locations</option>
                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($branch->location_id); ?>"><?php echo e($branch->location_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
    </div>
    <div class="chart-container">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- Compact Branch & P/L Side by Side -->
<div class="info-grid">
    <!-- Branch Performance -->
    <div class="info-card">
        <h3>🏢 Branch Performance</h3>
        <?php if($branches->isEmpty()): ?>
            <p style="color: #64748b; text-align: center; padding: 1rem;">No data available</p>
        <?php else: ?>
            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="branch-row">
                <div class="branch-name"><?php echo e($branch->location_name); ?></div>
                <div class="branch-metrics">
                    <span class="metric revenue">Rp <?php echo e(number_format($branch->total_revenue, 0, ',', '.')); ?></span>
                    <span class="metric orders"><?php echo e($branch->total_orders); ?> orders</span>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>

    <!-- P/L Summary -->
    <div class="info-card">
        <h3>💰 P/L Summary (This Month)</h3>
        <div class="pl-row">
            <span class="pl-label">Total Revenue</span>
            <span class="pl-value positive">Rp <?php echo e(number_format($pl['total_revenue'], 0, ',', '.')); ?></span>
        </div>
        <div class="pl-row">
            <span class="pl-label">Total Expenses</span>
            <span class="pl-value negative">Rp <?php echo e(number_format($pl['total_expenses'], 0, ',', '.')); ?></span>
        </div>
        <div class="pl-row highlight">
            <span class="pl-label">Net Profit</span>
            <span class="pl-value">Rp <?php echo e(number_format($pl['net_profit'], 0, ',', '.')); ?></span>
        </div>
    </div>
</div>

<!-- Recent Activity - Compact -->
<div class="activity-compact">
    <h3>Recent Activity</h3>
    <div class="activity-items">
        <?php if($activities->isEmpty()): ?>
            <p style="color: #64748b; text-align: center; padding: 1rem;">No recent activities</p>
        <?php else: ?>
            <?php $__currentLoopData = $activities->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="activity-item">
                <div class="activity-icon">🔧</div>
                <div class="activity-content">
                    <div class="activity-title"><?php echo e($activity->order_number); ?></div>
                    <div class="activity-meta">
                        <span class="activity-customer"><?php echo e($activity->customer_name); ?></span>
                        <span class="activity-status status-<?php echo e($activity->status); ?>"><?php echo e($activity->status); ?></span>
                        <span class="activity-time"><?php echo e($activity->updated_at->diffForHumans()); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/staff-dashboard.js')); ?>"></script>
<script>
    // Initialize revenue chart (basic implementation)
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Filter change handlers
    document.getElementById('periodFilter')?.addEventListener('change', loadRevenueData);
    document.getElementById('typeFilter')?.addEventListener('change', loadRevenueData);
    document.getElementById('locationFilter')?.addEventListener('change', loadRevenueData);

    function loadRevenueData() {
        // AJAX call to load revenue data
        // This would call an API endpoint to get filtered data
        console.log('Loading revenue data...');
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.staff', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\XAMPP\htdocs\frontendproject\laravel-mode\resources\views/staff/dashboard.blade.php ENDPATH**/ ?>