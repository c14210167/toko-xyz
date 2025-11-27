<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display customers list
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = $user->roles;
        $primary_role = $roles->isNotEmpty() ? $roles->first()->role_name : 'Staff';

        $query = User::where('user_type', 'customer')
            ->withCount('orders')
            ->select('users.*', DB::raw('MAX(orders.created_at) as last_order_date'))
            ->leftJoin('orders', 'users.user_id', '=', 'orders.user_id')
            ->groupBy('users.user_id');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('users.created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('users.created_at', '<=', $request->end_date);
        }

        $customers = $query->orderBy('users.created_at', 'DESC')->paginate(20);

        return view('staff.customers', compact('user', 'primary_role', 'customers'));
    }

    /**
     * Show customer detail
     */
    public function show($customerId)
    {
        $user = Auth::user();
        $roles = $user->roles;
        $primary_role = $roles->isNotEmpty() ? $roles->first()->role_name : 'Staff';

        $customer = User::with(['orders.location', 'orders.orderCost'])
            ->where('user_type', 'customer')
            ->findOrFail($customerId);

        $stats = [
            'total_orders' => $customer->orders->count(),
            'completed_orders' => $customer->orders->where('status', 'completed')->count(),
            'pending_orders' => $customer->orders->whereIn('status', ['pending', 'in_progress'])->count(),
            'total_spent' => $customer->orders->where('status', 'completed')
                ->sum(function($order) {
                    return $order->orderCost->total_cost ?? 0;
                }),
        ];

        return view('staff.customer-detail', compact('user', 'primary_role', 'customer', 'stats'));
    }
}
