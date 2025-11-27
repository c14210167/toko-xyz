<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user roles
        $roles = $user->roles;
        $primary_role = $roles->isNotEmpty() ? $roles->first()->role_name : 'Staff';

        // Get real-time stats
        $stats = [
            'active_orders' => Order::whereIn('status', ['pending', 'in_progress', 'waiting_parts'])->count(),
            'completed_today' => Order::where('status', 'completed')
                ->whereDate('orders.updated_at', today())
                ->count(),
            'pending_parts' => Order::where('status', 'waiting_parts')->count(),
            'revenue_today' => Order::where('status', 'completed')
                ->whereDate('orders.updated_at', today())
                ->join('order_costs', 'orders.order_id', '=', 'order_costs.order_id')
                ->sum('order_costs.total_cost') ?? 0,
        ];

        // Get branch performance data (this month)
        $branches = Location::select(
                'locations.location_id',
                'locations.name as location_name',
                DB::raw('COUNT(orders.order_id) as total_orders'),
                DB::raw('COALESCE(SUM(order_costs.total_cost), 0) as total_revenue')
            )
            ->leftJoin('orders', function($join) {
                $join->on('locations.location_id', '=', 'orders.location_id')
                    ->where('orders.status', '=', 'completed');
            })
            ->leftJoin('order_costs', 'orders.order_id', '=', 'order_costs.order_id')
            ->whereMonth('orders.created_at', now()->month)
            ->whereYear('orders.created_at', now()->year)
            ->groupBy('locations.location_id', 'locations.name')
            ->get();

        // Get P/L data (this month)
        $total_revenue = Order::where('status', 'completed')
            ->whereMonth('orders.created_at', now()->month)
            ->whereYear('orders.created_at', now()->year)
            ->join('order_costs', 'orders.order_id', '=', 'order_costs.order_id')
            ->sum('order_costs.total_cost') ?? 0;

        $total_expenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount') ?? 0;

        $net_profit = $total_revenue - $total_expenses;

        $pl = [
            'total_revenue' => $total_revenue,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
        ];

        // Motivational quotes
        $motivational_quotes = [
            'Keep pushing forward!',
            'You are doing great!',
            'Excellence is a habit',
            'Make today count',
            'Stay focused, stay strong'
        ];

        // Get recent activities
        $activities = Order::select(
                'orders.order_number',
                'orders.status',
                'orders.created_at',
                'orders.updated_at',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as customer_name")
            )
            ->join('users', 'orders.user_id', '=', 'users.user_id')
            ->orderBy('orders.updated_at', 'DESC')
            ->limit(5)
            ->get();

        return view('staff.dashboard', compact(
            'user',
            'primary_role',
            'stats',
            'branches',
            'pl',
            'motivational_quotes',
            'activities'
        ));
    }
}
