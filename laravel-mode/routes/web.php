<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\OrderController;
use App\Http\Controllers\Staff\CustomerController;
use App\Http\Controllers\Staff\InventoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Test Database Connection
Route::get('/test-db', function () {
    try {
        $users = DB::table('users')->count();
        $orders = DB::table('orders')->count();
        $roles = DB::table('roles')->count();

        return response()->json([
            'success' => true,
            'message' => 'Database connection successful! ✅',
            'data' => [
                'users_count' => $users,
                'orders_count' => $orders,
                'roles_count' => $roles,
                'database' => config('database.connections.mysql.database'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database connection failed! ❌',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Home Route
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Customer Routes (Authenticated)
Route::middleware(['auth', 'user.type:customer,staff,owner'])->group(function () {
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('customer.dashboard');

    Route::get('/order-history', function () {
        return view('customer.order-history');
    })->name('order.history');

    Route::get('/create-order', function () {
        return view('customer.create-order');
    })->name('order.create');
});

// Staff Routes
Route::prefix('staff')->name('staff.')->middleware(['auth', 'user.type:staff,owner'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/data', [OrderController::class, 'getData'])->name('data');
        Route::get('/{orderId}', [OrderController::class, 'show'])->name('show');
        Route::post('/{orderId}/status', [OrderController::class, 'updateStatus'])->name('update-status');
        Route::post('/{orderId}/sparepart', [OrderController::class, 'addSparepart'])->name('add-sparepart');
    });

    // Customers Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/{customerId}', [CustomerController::class, 'show'])->name('show');
    });

    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/{itemId}/add-stock', [InventoryController::class, 'addStock'])->name('add-stock');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/permissions', function () {
            return view('staff.settings.permissions');
        })->name('permissions');
    });
});
