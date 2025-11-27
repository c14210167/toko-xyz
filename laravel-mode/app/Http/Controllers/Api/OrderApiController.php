<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCost;
use App\Models\OrderDetail;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderApiController extends Controller
{
    /**
     * Get order detail (public - for customer tracking)
     */
    public function getOrderDetail($orderId)
    {
        try {
            $order = Order::with([
                'customer',
                'location',
                'technician',
                'costs',
                'details.inventoryItem',
                'statusHistory',
            ])->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'service_type' => 'required|string',
            'device_type' => 'required|string',
            'problem_description' => 'required|string',
            'location_id' => 'required|exists:locations,location_id',
            'is_member' => 'boolean',
            'customer_id' => 'required_if:is_member,true|exists:users,user_id',
            'guest_name' => 'required_if:is_member,false|string',
            'guest_phone' => 'required_if:is_member,false|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = null;

            // Handle member or guest customer
            if ($request->is_member) {
                $userId = $request->customer_id;
            } else {
                // Create or find guest customer
                $user = User::where('phone', $request->guest_phone)->first();

                if (!$user) {
                    $nameParts = explode(' ', $request->guest_name, 2);
                    $user = User::create([
                        'first_name' => $nameParts[0],
                        'last_name' => $nameParts[1] ?? '',
                        'email' => $request->guest_email ?? '',
                        'phone' => $request->guest_phone,
                        'password' => bcrypt('guest123'),
                        'user_type' => 'customer',
                        'role' => 'customer',
                    ]);
                }

                $userId = $user->user_id;
            }

            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'service_type' => $request->service_type,
                'device_type' => $request->device_type,
                'brand' => $request->brand,
                'model' => $request->model,
                'serial_number' => $request->serial_number,
                'problem_description' => $request->problem_description,
                'location_id' => $request->location_id,
                'status' => 'pending',
                'priority' => $request->priority ?? 'normal',
                'created_by' => Auth::id(),
            ]);

            // Create initial cost record
            OrderCost::create([
                'order_id' => $order->order_id,
                'service_cost' => 0,
                'sparepart_cost' => 0,
                'custom_cost' => 0,
                'total_cost' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order->load('customer', 'location', 'costs')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update service cost
     */
    public function updateServiceCost(Request $request, $orderId)
    {
        $request->validate([
            'service_cost' => 'required|numeric|min:0'
        ]);

        try {
            $orderCost = OrderCost::where('order_id', $orderId)->firstOrFail();

            $orderCost->service_cost = $request->service_cost;
            $orderCost->total_cost = $orderCost->service_cost + $orderCost->sparepart_cost + $orderCost->custom_cost;
            $orderCost->save();

            return response()->json([
                'success' => true,
                'message' => 'Service cost updated',
                'data' => $orderCost
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service cost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add sparepart to order (with auto inventory deduction)
     */
    public function addSparepart(Request $request, $orderId)
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,item_id',
            'quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($orderId);
            $item = InventoryItem::findOrFail($request->item_id);

            // Check stock availability
            if ($item->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: ' . $item->quantity
                ], 400);
            }

            // Calculate cost
            $subtotal = $item->unit_price * $request->quantity;

            // Add to order details
            $detail = OrderDetail::create([
                'order_id' => $orderId,
                'item_id' => $item->item_id,
                'item_name' => $item->name,
                'cost_type' => 'sparepart',
                'quantity' => $request->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $subtotal,
            ]);

            // Deduct from inventory
            $item->quantity -= $request->quantity;
            $item->save();

            // Record inventory transaction
            InventoryTransaction::create([
                'item_id' => $item->item_id,
                'transaction_type' => 'OUT',
                'quantity' => $request->quantity,
                'notes' => 'Used in order ' . $order->order_number,
                'order_id' => $orderId,
                'created_by' => Auth::id(),
            ]);

            // Update order cost
            $orderCost = OrderCost::where('order_id', $orderId)->first();
            $orderCost->sparepart_cost += $subtotal;
            $orderCost->total_cost = $orderCost->service_cost + $orderCost->sparepart_cost + $orderCost->custom_cost;
            $orderCost->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart added successfully',
                'data' => [
                    'detail' => $detail,
                    'order_cost' => $orderCost,
                    'item_stock' => $item->quantity
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add sparepart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove sparepart from order (with auto inventory return)
     */
    public function removeSparepart($orderId, $detailId)
    {
        DB::beginTransaction();

        try {
            $detail = OrderDetail::where('detail_id', $detailId)
                ->where('order_id', $orderId)
                ->where('cost_type', 'sparepart')
                ->firstOrFail();

            // Return to inventory
            if ($detail->item_id) {
                $item = InventoryItem::find($detail->item_id);
                if ($item) {
                    $item->quantity += $detail->quantity;
                    $item->save();

                    // Record inventory transaction
                    InventoryTransaction::create([
                        'item_id' => $item->item_id,
                        'transaction_type' => 'IN',
                        'quantity' => $detail->quantity,
                        'notes' => 'Returned from order ' . $detail->order->order_number,
                        'order_id' => $orderId,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Update order cost
            $orderCost = OrderCost::where('order_id', $orderId)->first();
            $orderCost->sparepart_cost -= $detail->subtotal;
            $orderCost->total_cost = $orderCost->service_cost + $orderCost->sparepart_cost + $orderCost->custom_cost;
            $orderCost->save();

            // Delete detail
            $detail->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart removed successfully',
                'data' => ['order_cost' => $orderCost]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove sparepart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add custom cost
     */
    public function addCustomCost(Request $request, $orderId)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();

        try {
            // Add to order details
            $detail = OrderDetail::create([
                'order_id' => $orderId,
                'item_name' => $request->description,
                'cost_type' => 'custom',
                'description' => $request->description,
                'quantity' => 1,
                'unit_price' => $request->amount,
                'subtotal' => $request->amount,
            ]);

            // Update order cost
            $orderCost = OrderCost::where('order_id', $orderId)->first();
            $orderCost->custom_cost += $request->amount;
            $orderCost->total_cost = $orderCost->service_cost + $orderCost->sparepart_cost + $orderCost->custom_cost;
            $orderCost->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Custom cost added successfully',
                'data' => [
                    'detail' => $detail,
                    'order_cost' => $orderCost
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add custom cost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,waiting_parts,completed,cancelled',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status;

            $order->status = $request->status;
            $order->save();

            // Record status change history
            $order->statusHistory()->create([
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'changed_by' => Auth::id(),
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated',
                'data' => $order->load('statusHistory')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
