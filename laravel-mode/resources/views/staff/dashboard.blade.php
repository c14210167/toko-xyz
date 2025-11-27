@extends('layouts.staff')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<!-- Compact Stats - Horizontal -->
<div class="compact-stats">
    <div class="stat-item">
        <div class="stat-value">{{ $stats['active_orders'] }}</div>
        <div class="stat-label">Active Orders</div>
    </div>
    <div class="stat-item success">
        <div class="stat-value">{{ $stats['completed_today'] }}</div>
        <div class="stat-label">Completed Today</div>
    </div>
    <div class="stat-item warning">
        <div class="stat-value">{{ $stats['pending_parts'] }}</div>
        <div class="stat-label">Pending Parts</div>
    </div>
    <div class="stat-item info">
        <div class="stat-value">Rp {{ number_format($stats['revenue_today'], 0, ',', '.') }}</div>
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
                @foreach($branches as $branch)
                    <option value="{{ $branch->location_id }}">{{ $branch->location_name }}</option>
                @endforeach
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
        @if($branches->isEmpty())
            <p style="color: #64748b; text-align: center; padding: 1rem;">No data available</p>
        @else
            @foreach($branches as $branch)
            <div class="branch-row">
                <div class="branch-name">{{ $branch->location_name }}</div>
                <div class="branch-metrics">
                    <span class="metric revenue">Rp {{ number_format($branch->total_revenue, 0, ',', '.') }}</span>
                    <span class="metric orders">{{ $branch->total_orders }} orders</span>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    <!-- P/L Summary -->
    <div class="info-card">
        <h3>💰 P/L Summary (This Month)</h3>
        <div class="pl-row">
            <span class="pl-label">Total Revenue</span>
            <span class="pl-value positive">Rp {{ number_format($pl['total_revenue'], 0, ',', '.') }}</span>
        </div>
        <div class="pl-row">
            <span class="pl-label">Total Expenses</span>
            <span class="pl-value negative">Rp {{ number_format($pl['total_expenses'], 0, ',', '.') }}</span>
        </div>
        <div class="pl-row highlight">
            <span class="pl-label">Net Profit</span>
            <span class="pl-value">Rp {{ number_format($pl['net_profit'], 0, ',', '.') }}</span>
        </div>
    </div>
</div>

<!-- Recent Activity - Compact -->
<div class="activity-compact">
    <h3>Recent Activity</h3>
    <div class="activity-items">
        @if($activities->isEmpty())
            <p style="color: #64748b; text-align: center; padding: 1rem;">No recent activities</p>
        @else
            @foreach($activities->take(3) as $activity)
            <div class="activity-item">
                <div class="activity-icon">🔧</div>
                <div class="activity-content">
                    <div class="activity-title">{{ $activity->order_number }}</div>
                    <div class="activity-meta">
                        <span class="activity-customer">{{ $activity->customer_name }}</span>
                        <span class="activity-status status-{{ $activity->status }}">{{ $activity->status }}</span>
                        <span class="activity-time">{{ $activity->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/staff-dashboard.js') }}"></script>
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
@endpush
