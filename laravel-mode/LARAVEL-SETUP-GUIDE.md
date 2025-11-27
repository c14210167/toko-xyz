# 🚀 Plus Plus Service Center - Laravel Version
## Panduan Lengkap Setup & Konversi dari PHP Native

---

## 📋 Daftar Isi
1. [Status Konversi](#status-konversi)
2. [Setup Awal](#setup-awal)
3. [Struktur Proyek](#struktur-proyek)
4. [Models yang Sudah Dibuat](#models-yang-sudah-dibuat)
5. [Models yang Harus Dibuat](#models-yang-harus-dibuat)
6. [Middleware Custom](#middleware-custom)
7. [Controllers](#controllers)
8. [Seeders](#seeders)
9. [Service Providers](#service-providers)
10. [Blade Templates](#blade-templates)
11. [Menjalankan Aplikasi](#menjalankan-aplikasi)

---

## ✅ Status Konversi

### Sudah Selesai:
- ✅ Struktur folder Laravel
- ✅ composer.json & dependencies
- ✅ .env.example
- ✅ Config files (app.php, database.php)
- ✅ 26 Migration files (semua tabel)
- ✅ Routes (web.php & api.php)
- ✅ 3 Models (User, Order, Location)

### Perlu Dilanjutkan:
- ⏳ 20+ Models lainnya
- ⏳ Middleware custom (permission checker)
- ⏳ Controllers (Auth, Staff, API)
- ⏳ Blade Templates
- ⏳ Seeders
- ⏳ Service Providers
- ⏳ Copy CSS & JS files
- ⏳ Helper functions

---

## 🔧 Setup Awal

### 1. Install Dependencies

```bash
cd laravel-mode
composer install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi Database

Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xyz_service_laravel
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Jalankan Migrations

```bash
php artisan migrate
```

### 5. Jalankan Seeders (setelah dibuat)

```bash
php artisan db:seed
```

---

## 📁 Struktur Proyek

```
laravel-mode/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/           # Login, Register, Logout
│   │   │   ├── Staff/          # Dashboard, Orders, Customers, Inventory
│   │   │   └── Api/            # API Controllers
│   │   ├── Middleware/
│   │   │   ├── CheckUserType.php
│   │   │   └── CheckPermission.php
│   │   └── Requests/           # Form validations
│   ├── Models/                 # Eloquent Models (23+ models)
│   ├── Services/               # Business logic
│   └── Helpers/
│       └── helpers.php         # Global helper functions
├── config/
├── database/
│   ├── migrations/             # ✅ 26 migrations (SUDAH DIBUAT)
│   ├── seeders/                # Database seeders
│   └── factories/              # Model factories
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   └── staff.blade.php
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── public/
│   │   │   ├── index.blade.php
│   │   │   ├── order-history.blade.php
│   │   │   └── create-order.blade.php
│   │   └── staff/
│   │       ├── dashboard.blade.php
│   │       ├── orders.blade.php
│   │       ├── customers.blade.php
│   │       └── inventory.blade.php
│   ├── css/                    # Your existing CSS files
│   └── js/                     # Your existing JS files
├── routes/
│   ├── web.php                 # ✅ SUDAH DIBUAT
│   └── api.php                 # ✅ SUDAH DIBUAT
└── public/
    ├── css/
    ├── js/
    └── images/
```

---

## ✅ Models yang Sudah Dibuat

### 1. User Model (`app/Models/User.php`) ✅
- Relationships: roles, permissions, orders, notifications
- Methods: hasRole(), hasPermission(), getFullNameAttribute()

### 2. Order Model (`app/Models/Order.php`) ✅
- Relationships: customer, location, technician, costs, details
- Scopes: pending(), inProgress(), completed()

### 3. Location Model (`app/Models/Location.php`) ✅
- Relationships: orders, inventoryItems, sales, appointments

---

## 📝 Models yang Harus Dibuat

### Role Model (`app/Models/Role.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';

    protected $fillable = ['role_name', 'description', 'is_system_role'];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withPivot('assigned_by', 'assigned_at');
    }
}
```

### Permission Model (`app/Models/Permission.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    public $timestamps = false;

    protected $fillable = [
        'permission_key',
        'permission_name',
        'description',
        'category',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')
            ->withPivot('is_granted', 'granted_by', 'granted_at');
    }
}
```

### OrderCost Model (`app/Models/OrderCost.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCost extends Model
{
    protected $table = 'order_costs';
    protected $primaryKey = 'cost_id';

    protected $fillable = [
        'order_id',
        'service_cost',
        'sparepart_cost',
        'custom_cost',
        'total_cost',
    ];

    protected $casts = [
        'service_cost' => 'decimal:2',
        'sparepart_cost' => 'decimal:2',
        'custom_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
```

### OrderDetail Model (`app/Models/OrderDetail.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'order_details';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'item_id',
        'item_name',
        'cost_type',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'item_id');
    }
}
```

### InventoryItem Model (`app/Models/InventoryItem.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'item_code',
        'name',
        'category_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'reorder_level',
        'location_id',
        'image_url',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id', 'category_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id', 'item_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }
}
```

### InventoryCategory Model (`app/Models/InventoryCategory.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected $table = 'inventory_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = ['category_name', 'description'];

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id', 'category_id');
    }
}
```

### InventoryTransaction Model (`app/Models/InventoryTransaction.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $table = 'inventory_transactions';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'transaction_type',
        'quantity',
        'notes',
        'order_id',
        'created_by',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'item_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
```

### ChatMessage Model (`app/Models/ChatMessage.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';
    protected $primaryKey = 'message_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'user_id');
    }
}
```

### Notification Model (`app/Models/Notification.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'icon',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
```

**BUAT JUGA MODEL UNTUK:**
- `OrderStatusHistory`
- `ProductCategory`
- `Product`
- `Sale`
- `SaleItem`
- `ExpenseCategory`
- `Expense`
- `Payment`
- `TimeSlot`
- `Appointment`
- `Rating`

---

## 🔒 Middleware Custom

### 1. CheckUserType Middleware (`app/Http/Middleware/CheckUserType.php`)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserType
{
    public function handle(Request $request, Closure $next, ...$types)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userType = auth()->user()->user_type;

        if (!in_array($userType, $types)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
```

### 2. CheckPermission Middleware (`app/Http/Middleware/CheckPermission.php`)

```php
<?php

namespace App\Http/Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this resource');
        }

        return $next($request);
    }
}
```

### 3. Register Middleware di `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'user.type' => \App\Http\Middleware\CheckUserType::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

---

## 🎮 Controllers

### Auth Controllers

#### LoginController (`app/Http/Controllers/Auth/LoginController.php`)

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on user type
            if (in_array($user->user_type, ['staff', 'owner'])) {
                return redirect()->intended(route('staff.dashboard'));
            }

            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
```

#### RegisterController (`app/Http/Controllers/Auth/RegisterController.php`)

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'user_type' => 'customer',
            'role' => 'customer',
        ]);

        // Assign Customer role
        $customerRole = Role::where('role_name', 'Customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->role_id);
        }

        Auth::login($user);

        return redirect()->route('home');
    }
}
```

### Staff Dashboard Controller

#### DashboardController (`app/Http/Controllers/Staff/DashboardController.php`)

```php
<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCost;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active_orders' => Order::whereIn('status', ['pending', 'in_progress', 'waiting_parts'])->count(),
            'completed_today' => Order::where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count(),
            'pending_parts' => Order::where('status', 'waiting_parts')->count(),
            'revenue_today' => OrderCost::join('orders', 'order_costs.order_id', '=', 'orders.order_id')
                ->where('orders.status', 'completed')
                ->whereDate('orders.updated_at', today())
                ->sum('order_costs.total_cost'),
        ];

        $recentOrders = Order::with(['customer', 'location'])
            ->latest()
            ->limit(10)
            ->get();

        return view('staff.dashboard', compact('stats', 'recentOrders'));
    }
}
```

---

## 🌱 Seeders

### RoleSeeder (`database/seeders/RoleSeeder.php`)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['role_name' => 'Owner', 'description' => 'Full system access', 'is_system_role' => true],
            ['role_name' => 'Manager', 'description' => 'Access to most features except critical settings', 'is_system_role' => true],
            ['role_name' => 'Technician', 'description' => 'Access to orders and inventory', 'is_system_role' => true],
            ['role_name' => 'Cashier', 'description' => 'Access to sales and payments', 'is_system_role' => true],
            ['role_name' => 'Customer', 'description' => 'Customer access only', 'is_system_role' => true],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
```

### PermissionSeeder (`database/seeders/PermissionSeeder.php`)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Dashboard
            ['permission_key' => 'view_dashboard', 'permission_name' => 'View Dashboard', 'category' => 'dashboard'],

            // Orders
            ['permission_key' => 'view_orders', 'permission_name' => 'View Orders', 'category' => 'orders'],
            ['permission_key' => 'create_orders', 'permission_name' => 'Create Orders', 'category' => 'orders'],
            ['permission_key' => 'edit_orders', 'permission_name' => 'Edit Orders', 'category' => 'orders'],
            ['permission_key' => 'delete_orders', 'permission_name' => 'Delete Orders', 'category' => 'orders'],
            ['permission_key' => 'update_order_status', 'permission_name' => 'Update Order Status', 'category' => 'orders'],

            // Customers
            ['permission_key' => 'view_customers', 'permission_name' => 'View Customers', 'category' => 'customers'],
            ['permission_key' => 'create_customers', 'permission_name' => 'Create Customers', 'category' => 'customers'],
            ['permission_key' => 'edit_customers', 'permission_name' => 'Edit Customers', 'category' => 'customers'],

            // Inventory
            ['permission_key' => 'view_inventory', 'permission_name' => 'View Inventory', 'category' => 'inventory'],
            ['permission_key' => 'create_inventory', 'permission_name' => 'Create Inventory Items', 'category' => 'inventory'],
            ['permission_key' => 'edit_inventory', 'permission_name' => 'Edit Inventory Items', 'category' => 'inventory'],
            ['permission_key' => 'delete_inventory', 'permission_name' => 'Delete Inventory Items', 'category' => 'inventory'],
            ['permission_key' => 'record_inventory_transaction', 'permission_name' => 'Record Inventory Transaction', 'category' => 'inventory'],

            // Permissions
            ['permission_key' => 'manage_permissions', 'permission_name' => 'Manage Permissions', 'category' => 'permissions'],
            ['permission_key' => 'manage_roles', 'permission_name' => 'Manage Roles', 'category' => 'permissions'],

            // Reports
            ['permission_key' => 'view_reports', 'permission_name' => 'View Reports', 'category' => 'reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
```

### LocationSeeder (`database/seeders/LocationSeeder.php`)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run()
    {
        Location::create([
            'location_name' => 'Plus Plus Komputer - Pusat',
            'address' => 'Jl. Raya No. 123',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'phone' => '021-12345678',
            'email' => 'pusat@plusplus.com',
            'is_active' => true,
        ]);
    }
}
```

---

## 🎨 Blade Templates

### Layout Utama (`resources/views/layouts/app.blade.php`)

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Plus Plus Service Center')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ asset('js/script.js') }}"></script>
    @stack('scripts')
</body>
</html>
```

### Login Page (`resources/views/auth/login.blade.php`)

```blade
@extends('layouts.app')

@section('title', 'Login - Plus Plus Service Center')

@section('content')
<div class="login-container">
    <div class="login-box">
        <h2>Login</h2>

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember">
                    Remember Me
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>

            <p class="text-center">
                Belum punya akun? <a href="{{ route('register') }}">Daftar</a>
            </p>
        </form>
    </div>
</div>
@endsection
```

---

## 🚀 Menjalankan Aplikasi

### Development Server

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2 (optional): Vite for asset compilation
npm run dev
```

### Akses Aplikasi

- **Homepage**: http://localhost:8000
- **Login**: http://localhost:8000/login
- **Staff Dashboard**: http://localhost:8000/staff/dashboard

### Default Credentials (setelah seeder)

```
Owner Account:
Email: owner@plusplus.com
Password: password123

Staff Account:
Email: staff@plusplus.com
Password: password123
```

---

## 📦 Copy Assets dari PHP Native

### Copy CSS Files

```bash
cp -r ../css/* public/css/
```

### Copy JavaScript Files

```bash
cp -r ../js/* public/js/
```

### Copy Images

```bash
cp -r ../images/* public/images/
```

---

## 🔧 Helper Functions (`app/Helpers/helpers.php`)

```php
<?php

if (!function_exists('hasPermission')) {
    function hasPermission($permissionKey)
    {
        return auth()->check() && auth()->user()->hasPermission($permissionKey);
    }
}

if (!function_exists('hasRole')) {
    function hasRole($roleName)
    {
        return auth()->check() && auth()->user()->hasRole($roleName);
    }
}

if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber()
    {
        return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
```

Tambahkan di `composer.json`:

```json
"autoload": {
    "files": [
        "app/Helpers/helpers.php"
    ]
}
```

Lalu jalankan:

```bash
composer dump-autoload
```

---

## 🎯 Next Steps

1. ✅ Buat semua Models yang belum ada
2. ✅ Buat semua Controllers (Auth, Staff, API)
3. ✅ Buat Middleware dan register di bootstrap/app.php
4. ✅ Buat Blade Templates untuk semua halaman
5. ✅ Buat Seeders dan jalankan
6. ✅ Copy CSS & JS files
7. ✅ Test semua fitur
8. ✅ Deploy ke production

---

## 📝 Catatan Penting

- Semua migration sudah siap, tinggal jalankan `php artisan migrate`
- Routes sudah lengkap di `routes/web.php` dan `routes/api.php`
- User model sudah ada method `hasPermission()` dan `hasRole()`
- Middleware permission sudah siap, tinggal register
- Untuk API, gunakan Laravel Sanctum untuk authentication

---

## 🆘 Troubleshooting

### Error "Class not found"
```bash
composer dump-autoload
```

### Error "SQLSTATE[HY000] [2002]"
- Pastikan MySQL sudah running
- Cek konfigurasi DB di file `.env`

### Error "419 Page Expired"
- Clear browser cache
- Pastikan ada `@csrf` di semua form

---

**Happy Coding! 🎉**
