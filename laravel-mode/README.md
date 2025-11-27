# 🚀 Plus Plus Komputer Service Center - Laravel Version

Konversi lengkap dari PHP Native ke Laravel 10 untuk sistem management service center.

---

## 📊 Status Project

```
📁 Folder Structure        : ✅ 100% Complete
🗄️  Database Migrations     : ✅ 100% Complete (26 tables)
🎯 Routes (Web & API)      : ✅ 100% Complete
📦 Models                  : ⏳ 20% Complete (3/23 models)
🎮 Controllers             : ⏳ 10% Complete (sample code provided)
🎨 Blade Templates         : ⏳ 0% Complete (structure ready)
🔧 Middleware              : ✅ 100% Code provided
🌱 Seeders                 : ✅ 100% Code provided
📚 Documentation           : ✅ 100% Complete
```

---

## ⚡ Quick Start

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

### 3. Configure Database

Edit `.env` file:
```env
DB_DATABASE=xyz_service_laravel
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Run Seeders

```bash
php artisan db:seed
```

### 6. Start Server

```bash
php artisan serve
```

Visit: **http://localhost:8000**

---

## 📖 Dokumentasi Lengkap

Baca file **[LARAVEL-SETUP-GUIDE.md](LARAVEL-SETUP-GUIDE.md)** untuk:

- ✅ Panduan setup lengkap
- ✅ Kode semua Models yang perlu dibuat
- ✅ Kode semua Controllers
- ✅ Kode Middleware
- ✅ Kode Seeders
- ✅ Template Blade
- ✅ Helper functions
- ✅ Troubleshooting

---

## 🎯 Apa yang Sudah Siap?

### ✅ Database Structure (26 Tables)

Semua tabel sudah dikonversi ke Laravel migrations:

1. **RBAC System** (5 tables)
   - roles
   - permissions
   - role_permissions
   - user_roles
   - user_permissions

2. **Core Tables** (4 tables)
   - users
   - locations
   - orders
   - notifications

3. **Order Management** (3 tables)
   - order_costs
   - order_details
   - order_status_history

4. **Inventory** (3 tables)
   - inventory_categories
   - inventory_items
   - inventory_transactions

5. **Products & Sales** (4 tables)
   - product_categories
   - products
   - sales
   - sale_items

6. **Expenses** (2 tables)
   - expense_categories
   - expenses

7. **Others** (5 tables)
   - payments
   - time_slots
   - appointments
   - ratings
   - chat_messages

### ✅ Routes

**Web Routes** (`routes/web.php`):
- Public pages (home, login, register)
- Customer pages (order history, create order)
- Staff pages (dashboard, orders, customers, inventory, settings)

**API Routes** (`routes/api.php`):
- Customer APIs (order detail, messages)
- Staff APIs (19+ endpoints)
  - Inventory management
  - Order management
  - Customer management
  - RBAC management
  - Chat/messaging

### ✅ Models (3 of 23)

1. **User** - Dengan RBAC integration
2. **Order** - Dengan relationships lengkap
3. **Location** - Base model

**20 Models lainnya**: Kode lengkap ada di LARAVEL-SETUP-GUIDE.md

### ✅ Configuration

- composer.json dengan dependencies
- .env.example
- config/app.php
- config/database.php
- bootstrap/app.php
- public/index.php
- artisan command file

---

## 🔑 Fitur Utama

### 1. **Authentication System**
- Login dengan email & password
- Registration untuk customer
- Session management
- Remember me functionality

### 2. **Role-Based Access Control (RBAC)**
- 5 predefined roles (Owner, Manager, Technician, Cashier, Customer)
- 50+ permissions
- User-specific permission overrides
- Middleware untuk permission checking

### 3. **Order Management**
- Create order (member/guest)
- Edit order dengan sparepart management
- Cost calculation (service + spareparts + custom)
- Status tracking
- Order history

### 4. **Inventory Management**
- Item management
- Stock tracking
- Auto-deduction saat add sparepart
- Auto-return saat remove sparepart
- Low stock alerts
- Transaction history

### 5. **Customer Management**
- Customer database
- Search & autocomplete
- Order history per customer
- Chat messaging

### 6. **Dashboard & Analytics**
- Active orders count
- Revenue tracking
- Monthly P&L
- Recent activity feed

---

## 🛠️ Technology Stack

- **Framework**: Laravel 10
- **PHP**: ^8.0
- **Database**: MySQL 5.7+
- **Frontend**: Blade Templates
- **CSS**: Native (from PHP version)
- **JavaScript**: Native (from PHP version)
- **Authentication**: Laravel Breeze style
- **RBAC**: Custom implementation (compatible with Spatie)

---

## 📁 Project Structure

```
laravel-mode/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              ← Login, Register
│   │   │   ├── Staff/             ← Dashboard, Orders, Customers
│   │   │   └── Api/               ← API Controllers
│   │   ├── Middleware/            ← Permission checking
│   │   └── Requests/
│   ├── Models/                    ← Eloquent Models (3 created, 20 to go)
│   ├── Services/                  ← Business logic
│   └── Helpers/
│       └── helpers.php            ← Global functions
├── database/
│   ├── migrations/                ← ✅ 26 migrations ready
│   ├── seeders/                   ← Code provided in guide
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/               ← Master templates
│   │   ├── auth/                  ← Login, Register
│   │   ├── public/                ← Homepage, Order tracking
│   │   └── staff/                 ← Dashboard, Management
│   ├── css/                       ← Copy from ../css/
│   └── js/                        ← Copy from ../js/
├── routes/
│   ├── web.php                    ← ✅ All routes defined
│   └── api.php                    ← ✅ All API routes defined
└── public/
    ├── css/
    ├── js/
    └── images/
```

---

## 🎯 Yang Perlu Dilakukan Selanjutnya

### 1. Buat Models (20 models tersisa)

Semua kode lengkap ada di `LARAVEL-SETUP-GUIDE.md`, tinggal copy-paste:
- Role, Permission
- OrderCost, OrderDetail, OrderStatusHistory
- InventoryCategory, InventoryItem, InventoryTransaction
- ChatMessage, Notification
- ProductCategory, Product, Sale, SaleItem
- ExpenseCategory, Expense
- Payment, TimeSlot, Appointment, Rating

### 2. Buat Controllers

Template dan pattern lengkap ada di guide:
- Auth Controllers (Login, Register, Logout)
- Staff Controllers (Dashboard, Orders, Customers, Inventory)
- API Controllers (Order, Inventory, Customer, RBAC)

### 3. Buat Middleware & Register

```bash
php artisan make:middleware CheckUserType
php artisan make:middleware CheckPermission
```

Copy kode dari guide, lalu register di `bootstrap/app.php`

### 4. Buat Seeders

```bash
php artisan make:seeder RoleSeeder
php artisan make:seeder PermissionSeeder
php artisan make:seeder LocationSeeder
php artisan make:seeder UserSeeder
```

Copy kode dari guide

### 5. Buat Blade Templates

Convert semua `.php` files ke `.blade.php`:
- Copy HTML structure
- Replace `<?php echo` dengan `{{ }}`
- Add `@extends`, `@section`, `@include`
- Replace PHP logic dengan Blade directives

### 6. Copy Assets

```bash
cp -r ../css/* public/css/
cp -r ../js/* public/js/
cp -r ../images/* public/images/
```

### 7. Test & Deploy

```bash
php artisan test
php artisan optimize
```

---

## 💡 Tips

1. **Gunakan Artisan Commands**
   ```bash
   php artisan make:model ModelName
   php artisan make:controller ControllerName
   php artisan make:middleware MiddlewareName
   php artisan make:seeder SeederName
   ```

2. **Enable Query Log** (untuk debugging)
   ```php
   DB::enableQueryLog();
   // your code
   dd(DB::getQueryLog());
   ```

3. **Clear Cache** (jika ada masalah)
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

---

## 📞 Support

Jika ada pertanyaan atau masalah:
1. Baca file **LARAVEL-SETUP-GUIDE.md**
2. Cek bagian Troubleshooting di guide
3. Pastikan semua dependencies sudah ter-install
4. Cek file `.env` sudah benar

---

## 📝 Catatan

- Project ini adalah konversi 1:1 dari PHP native ke Laravel
- Semua fitur dari versi PHP native sudah dipetakan ke Laravel
- Database structure sama persis (untuk migrasi data mudah)
- Business logic tetap sama, hanya menggunakan Laravel conventions

---

## 🎉 Selamat Mencoba!

Laravel version ini memberikan:
- ✅ Better code organization
- ✅ Built-in security features
- ✅ Eloquent ORM untuk database operations
- ✅ Blade templating engine
- ✅ Migration system untuk database versioning
- ✅ Artisan commands untuk development
- ✅ Better testing capabilities
- ✅ Modern PHP practices

**Good luck with your Laravel migration! 🚀**
