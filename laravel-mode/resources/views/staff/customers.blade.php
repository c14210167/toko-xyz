@extends('layouts.staff')

@section('title', 'Customers Management')
@section('page-title', 'Customers Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/customers.css') }}">
@endpush

@section('content')
<div class="customers-header">
    <div class="filters-row">
        <form action="{{ route('staff.customers.index') }}" method="GET" class="filter-form">
            <input type="text" name="search" class="search-input" placeholder="Search customers..." value="{{ request('search') }}">
            <input type="date" name="start_date" class="date-input" value="{{ request('start_date') }}">
            <input type="date" name="end_date" class="date-input" value="{{ request('end_date') }}">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </form>
    </div>
</div>

<div class="customers-stats">
    <div class="stat-card">
        <div class="stat-value">{{ $customers->total() }}</div>
        <div class="stat-label">Total Customers</div>
    </div>
</div>

<div class="customers-container">
    <table class="customers-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Total Orders</th>
                <th>Last Order</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
            <tr>
                <td>{{ $customer->first_name }} {{ $customer->last_name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->orders_count }}</td>
                <td>{{ $customer->last_order_date ? \Carbon\Carbon::parse($customer->last_order_date)->format('d M Y') : 'Never' }}</td>
                <td>{{ \Carbon\Carbon::parse($customer->created_at)->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('staff.customers.show', $customer->user_id) }}" class="btn btn-sm btn-info">View Details</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="no-data">No customers found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $customers->links() }}
    </div>
</div>
@endsection
