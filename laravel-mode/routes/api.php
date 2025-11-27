<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\RbacApiController;
use App\Http\Controllers\Api\MessageApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API Routes (Customer-facing)
Route::prefix('customer')->group(function () {
    Route::get('/order-detail/{order_id}', [OrderApiController::class, 'getOrderDetail']);
    Route::post('/send-message', [MessageApiController::class, 'sendMessage']);
    Route::get('/messages/{order_id}', [MessageApiController::class, 'getMessages']);
});

// Staff API Routes (Protected)
Route::prefix('staff')->middleware(['auth:sanctum', 'user.type:staff,owner'])->group(function () {

    // Inventory APIs
    Route::get('/inventory', [InventoryApiController::class, 'getInventory'])->middleware('permission:view_inventory');
    Route::post('/inventory', [InventoryApiController::class, 'addInventoryItem'])->middleware('permission:create_inventory');
    Route::post('/inventory/transaction', [InventoryApiController::class, 'recordTransaction'])->middleware('permission:record_inventory_transaction');
    Route::get('/inventory/low-stock', [InventoryApiController::class, 'getLowStockAlerts'])->middleware('permission:view_inventory');
    Route::get('/inventory/search', [InventoryApiController::class, 'searchInventory'])->middleware('permission:view_inventory');

    // Order Management APIs
    Route::post('/orders', [OrderApiController::class, 'createOrder'])->middleware('permission:create_orders');
    Route::get('/orders/{id}', [OrderApiController::class, 'getOrderDetail'])->middleware('permission:view_orders');
    Route::put('/orders/{id}/service-cost', [OrderApiController::class, 'updateServiceCost'])->middleware('permission:edit_orders');
    Route::post('/orders/{id}/sparepart', [OrderApiController::class, 'addSparepart'])->middleware('permission:edit_orders');
    Route::delete('/orders/{id}/sparepart/{detail_id}', [OrderApiController::class, 'removeSparepart'])->middleware('permission:edit_orders');
    Route::post('/orders/{id}/custom-cost', [OrderApiController::class, 'addCustomCost'])->middleware('permission:edit_orders');
    Route::delete('/orders/{id}/custom-cost/{detail_id}', [OrderApiController::class, 'removeCustomCost'])->middleware('permission:edit_orders');
    Route::put('/orders/{id}/status', [OrderApiController::class, 'updateOrderStatus'])->middleware('permission:update_order_status');

    // Customer APIs
    Route::get('/customers/search', [CustomerApiController::class, 'searchCustomers'])->middleware('permission:view_customers');
    Route::get('/customers/{id}', [CustomerApiController::class, 'getCustomerDetail'])->middleware('permission:view_customers');

    // RBAC Management APIs
    Route::get('/roles', [RbacApiController::class, 'getAllRoles'])->middleware('permission:manage_permissions');
    Route::get('/permissions', [RbacApiController::class, 'getAllPermissions'])->middleware('permission:manage_permissions');
    Route::post('/users/{user_id}/roles', [RbacApiController::class, 'assignUserRole'])->middleware('permission:manage_permissions');
    Route::delete('/users/{user_id}/roles/{role_id}', [RbacApiController::class, 'removeUserRole'])->middleware('permission:manage_permissions');
    Route::get('/users/{user_id}/permissions', [RbacApiController::class, 'getUserPermissions'])->middleware('permission:manage_permissions');
    Route::put('/users/{user_id}/permissions/{permission_id}', [RbacApiController::class, 'updateUserPermission'])->middleware('permission:manage_permissions');

    // Chat/Messaging
    Route::get('/chat/messages/{order_id}', [MessageApiController::class, 'getChatMessages'])->middleware('permission:view_orders');
    Route::post('/chat/messages', [MessageApiController::class, 'sendChatMessage'])->middleware('permission:view_orders');
});
