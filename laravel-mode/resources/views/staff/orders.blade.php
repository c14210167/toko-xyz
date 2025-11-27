@extends('layouts.staff')

@section('title', 'Orders Management')
@section('page-title', 'Orders Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-orders.css') }}">
<link rel="stylesheet" href="{{ asset('css/order-management.css') }}">
@endpush

@section('content')
<div class="orders-header">
    <div class="filters-row">
        <select id="statusFilter" class="filter-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="waiting_parts">Waiting Parts</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="on_hold">On Hold</option>
        </select>

        <select id="locationFilter" class="filter-select">
            <option value="all">All Locations</option>
            @foreach($locations as $location)
                <option value="{{ $location->location_id }}">{{ $location->name }}</option>
            @endforeach
        </select>

        <input type="text" id="searchInput" class="search-input" placeholder="Search orders...">

        <button class="btn btn-primary" onclick="loadOrders()">🔍 Search</button>
        <button class="btn btn-success" onclick="createNewOrder()">➕ New Order</button>
    </div>
</div>

<div class="orders-container">
    <table class="orders-table" id="ordersTable">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Device</th>
                <th>Location</th>
                <th>Status</th>
                <th>Cost</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            <tr>
                <td colspan="8" class="loading">Loading orders...</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/staff-orders.js') }}"></script>
<script>
    function loadOrders() {
        const status = document.getElementById('statusFilter').value;
        const location = document.getElementById('locationFilter').value;
        const search = document.getElementById('searchInput').value;

        fetch(`{{ route('staff.orders.data') }}?status=${status}&location_id=${location}&search=${search}`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('ordersTableBody');
                if (data.success && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(order => `
                        <tr>
                            <td>${order.order_number}</td>
                            <td>${order.user ? order.user.first_name + ' ' + order.user.last_name : 'N/A'}</td>
                            <td>${order.device_brand} ${order.device_type}</td>
                            <td>${order.location ? order.location.name : 'N/A'}</td>
                            <td><span class="status-badge status-${order.status}">${order.status}</span></td>
                            <td>Rp ${order.order_cost ? Number(order.order_cost.total_cost).toLocaleString('id-ID') : '0'}</td>
                            <td>${new Date(order.created_at).toLocaleDateString('id-ID')}</td>
                            <td>
                                <a href="/staff/orders/${order.order_id}" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="no-data">No orders found</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('ordersTableBody').innerHTML = '<tr><td colspan="8" class="error">Failed to load orders</td></tr>';
            });
    }

    function createNewOrder() {
        window.location.href = '{{ route("order.create") }}';
    }

    // Load orders on page load
    document.addEventListener('DOMContentLoaded', loadOrders);
</script>
@endpush
