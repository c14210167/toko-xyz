<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryTransaction;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    /**
     * Display inventory list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->roles;
        $primary_role = $roles->isNotEmpty() ? $roles->first()->role_name : 'Staff';

        // Get filters data
        $categories = InventoryCategory::orderBy('category_name')->get();
        $locations = Location::orderBy('name')->get();

        $query = InventoryItem::with(['category', 'location']);

        // Apply filters
        if ($request->filled('category_id') && $request->category_id != 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('location_id') && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            if ($request->status == 'low_stock') {
                $query->lowStock();
            } elseif ($request->status == 'out_of_stock') {
                $query->where('quantity', 0);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        $items = $query->orderBy('item_name')->paginate(50);

        return view('staff.inventory', compact(
            'user',
            'primary_role',
            'items',
            'categories',
            'locations'
        ));
    }

    /**
     * Add stock
     */
    public function addStock(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $item = InventoryItem::findOrFail($itemId);

            // Update quantity
            $item->quantity += $request->quantity;
            $item->save();

            // Record transaction
            InventoryTransaction::create([
                'item_id' => $itemId,
                'transaction_type' => 'in',
                'quantity' => $request->quantity,
                'notes' => $request->notes,
                'performed_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan stok: ' . $e->getMessage()
            ], 500);
        }
    }
}
