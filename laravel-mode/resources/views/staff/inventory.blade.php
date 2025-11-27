@extends('layouts.staff')

@section('title', 'Inventory Management')
@section('page-title', 'Inventory Management')

@push('styles')
<style>
    .inventory-filters { margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; }
    .filter-select { padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 8px; }
    .inventory-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
    .inventory-table th { background: #1e293b; color: white; padding: 1rem; text-align: left; }
    .inventory-table td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
    .stock-low { color: #f59e0b; font-weight: bold; }
    .stock-ok { color: #10b981; }
    .stock-out { color: #ef4444; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="inventory-filters">
    <form action="{{ route('staff.inventory.index') }}" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; width: 100%;">
        <select name="category_id" class="filter-select">
            <option value="all">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->category_id }}" {{ request('category_id') == $category->category_id ? 'selected' : '' }}>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>

        <select name="location_id" class="filter-select">
            <option value="all">All Locations</option>
            @foreach($locations as $location)
                <option value="{{ $location->location_id }}" {{ request('location_id') == $location->location_id ? 'selected' : '' }}>
                    {{ $location->name }}
                </option>
            @endforeach
        </select>

        <select name="status" class="filter-select">
            <option value="">All Stock Status</option>
            <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
            <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
        </select>

        <input type="text" name="search" class="filter-select" placeholder="Search items..." value="{{ request('search') }}" style="flex: 1; min-width: 200px;">

        <button type="submit" class="btn btn-primary">🔍 Filter</button>
    </form>
</div>

<div class="inventory-container">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Location</th>
                <th>Quantity</th>
                <th>Reorder Level</th>
                <th>Unit Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->category ? $item->category->category_name : 'N/A' }}</td>
                <td>{{ $item->location ? $item->location->name : 'N/A' }}</td>
                <td>
                    <span class="{{ $item->quantity == 0 ? 'stock-out' : ($item->quantity <= $item->reorder_level ? 'stock-low' : 'stock-ok') }}">
                        {{ $item->quantity }}
                    </span>
                </td>
                <td>{{ $item->reorder_level }}</td>
                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="addStock({{ $item->item_id }})">➕ Add Stock</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="no-data">No inventory items found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">
        {{ $items->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    function addStock(itemId) {
        const quantity = prompt('Enter quantity to add:');
        if (quantity && !isNaN(quantity) && quantity > 0) {
            fetch(`/staff/inventory/${itemId}/add-stock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ quantity: parseInt(quantity) })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Stock added successfully!');
                    location.reload();
                } else {
                    alert('Failed to add stock: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add stock');
            });
        }
    }
</script>
@endpush
