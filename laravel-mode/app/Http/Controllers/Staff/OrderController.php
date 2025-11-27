<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\OrderDetail;
use App\Models\OrderCost;
use App\Models\OrderStatusHistory;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display orders list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->roles;
        $primary_role = $roles->isNotEmpty() ? $roles->first()->role_name : 'Staff';

        // Get all locations for filter
        $locations = Location::orderBy('name')->get();

        return view('staff.orders', compact('user', 'primary_role', 'locations'));
    }

    /**
     * Get orders data (for AJAX)
     */
    public function getData(Request $request)
    {
        $query = Order::with(['user', 'location', 'orderCost'])
            ->select('orders.*');

        // Apply filters
        if ($request->filled('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('location_id') && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhere('device_type', 'LIKE', "%{$search}%")
                  ->orWhere('device_brand', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                  });
            });
        }

        // Order by
        $query->orderBy('created_at', 'DESC');

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,waiting_parts,completed,cancelled,on_hold',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;

            $order->status = $request->status;
            $order->save();

            // Record status change in history
            OrderStatusHistory::create([
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'changed_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status order berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add sparepart to order
     */
    public function addSparepart(Request $request, $orderId)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,item_id',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($orderId);
            $item = InventoryItem::findOrFail($request->inventory_item_id);

            // Check stock
            if ($item->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak mencukupi'
                ], 400);
            }

            // Add to order details
            $detail = OrderDetail::create([
                'order_id' => $orderId,
                'item_type' => 'sparepart',
                'item_id' => $request->inventory_item_id,
                'description' => $item->item_name,
                'quantity' => $request->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->unit_price * $request->quantity,
            ]);

            // Deduct inventory
            $item->quantity -= $request->quantity;
            $item->save();

            // Update order cost
            $this->updateOrderCost($orderId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart berhasil ditambahkan',
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan sparepart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order cost calculation
     */
    private function updateOrderCost($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Calculate sparepart cost
        $sparepartCost = OrderDetail::where('order_id', $orderId)
            ->where('item_type', 'sparepart')
            ->sum('total_price');

        // Get or create order cost
        $orderCost = OrderCost::firstOrNew(['order_id' => $orderId]);
        $orderCost->sparepart_cost = $sparepartCost;
        $orderCost->total_cost = $orderCost->service_cost + $sparepartCost;
        $orderCost->save();
    }

    /**
     * Show order detail
     */
    public function show($orderId)
    {
        $order = Order::with([
            'user',
            'location',
            'orderDetails.inventoryItem',
            'orderCost',
            'statusHistory.changer'
        ])->findOrFail($orderId);

        return view('staff.order-detail', compact('order'));
    }
}
