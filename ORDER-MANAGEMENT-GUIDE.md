# 🔧 ORDER MANAGEMENT - Complete Guide

## ✅ APA YANG SUDAH DIBUAT (Backend API - 100% Complete)

### 1. API Endpoints yang Sudah Dibuat:

| API File | Fungsi | Status |
|----------|--------|--------|
| **search-customers.php** | Search existing members/customers | ✅ Complete |
| **create-order.php** | Create order (member or guest) | ✅ Complete |
| **get-order-detail.php** | Get complete order details | ✅ Complete |
| **update-service-cost.php** | Update service cost | ✅ Complete |
| **add-sparepart-to-order.php** | Add sparepart + auto-deduct inventory | ✅ Complete |
| **remove-sparepart-from-order.php** | Remove sparepart + return to inventory | ✅ Complete |
| **add-custom-cost.php** | Add custom cost (transport, admin, etc.) | ✅ Complete |
| **search-inventory-items.php** | Search inventory for spareparts | ✅ Complete |

**Lokasi:** `staff/api/`

---

## 🎯 FITUR YANG SUDAH DIIMPLEMENTASI (Backend)

### Feature 1: Create Order dengan Member Check ✅

**Flow:**
1. User klik "New Order"
2. System tanya: "Apakah ada member?"
   - **YES:** Search existing customers
   - **NO:** Input manual (nama, phone, email)
3. Buat order baru

**API:** `create-order.php`
- Terima parameter `is_member` (true/false)
- Jika member: `customer_id` required
- Jika guest: `guest_name`, `guest_phone`, `guest_email`
- Auto-create guest account jika belum ada

### Feature 2: Edit Order Mendalam dengan Spareparts ✅

**Capabilities:**
- ✅ Update service cost
- ✅ Add sparepart (auto-deduct dari inventory)
- ✅ Remove sparepart (auto-return ke inventory)
- ✅ Add custom cost (transport, admin fee, dll)
- ✅ Real-time total calculation
- ✅ Low stock notification

**Flow Add Sparepart:**
1. Search inventory item
2. Pilih item + quantity
3. System check stock availability
4. Deduct dari inventory (UPDATE inventory_items)
5. Create transaction record (inventory_transactions)
6. Update order costs (order_costs table)
7. Send low stock alert jika perlu

**Flow Remove Sparepart:**
1. Pilih sparepart dari list
2. Return quantity ke inventory
3. Create return transaction
4. Update order costs
5. Delete original OUT transaction

---

## 📊 DATABASE CHANGES

### Inventory Transactions dengan Order Linking:
```sql
-- Sudah ada di schema, tidak perlu perubahan!
inventory_transactions
- order_id (INT) -- Link ke order
- transaction_type ('IN', 'OUT', 'ADJUSTMENT')
```

### Order Costs dengan Custom Costs:
```sql
-- Custom costs disimpan sebagai JSON di notes field
order_costs
- notes (TEXT) -- Stores JSON: {"custom_costs": [...]}
```

**Format Custom Costs JSON:**
```json
{
  "custom_costs": [
    {
      "id": "unique_id",
      "name": "Transport Fee",
      "description": "Delivery to customer",
      "amount": 50000,
      "added_at": "2025-01-21 10:30:00",
      "added_by": 1
    }
  ]
}
```

---

## 🚀 CARA MENGGUNAKAN API

### 1. Search Customers (untuk member check)

**Endpoint:** `GET /staff/api/search-customers.php?search=john`

**Response:**
```json
{
  "success": true,
  "customers": [
    {
      "user_id": 5,
      "full_name": "John Doe",
      "email": "john@example.com",
      "phone": "08123456789",
      "address": "Jakarta"
    }
  ]
}
```

### 2. Create Order

**Endpoint:** `POST /staff/api/create-order.php`

**Request Body (Member):**
```json
{
  "is_member": true,
  "customer_id": 5,
  "service_type": "Repair",
  "device_type": "Laptop",
  "brand": "Asus",
  "model": "ROG",
  "problem_description": "Screen not working",
  "location_id": 1,
  "priority": "normal"
}
```

**Request Body (Guest):**
```json
{
  "is_member": false,
  "guest_name": "Jane Doe",
  "guest_phone": "08198765432",
  "guest_email": "jane@example.com",
  "service_type": "Repair",
  "device_type": "Laptop",
  "problem_description": "Blue screen error",
  "location_id": 1
}
```

### 3. Get Order Detail

**Endpoint:** `GET /staff/api/get-order-detail.php?order_id=123`

**Response:**
```json
{
  "success": true,
  "order": {...},
  "costs": {...},
  "spareparts": [
    {
      "item_id": 10,
      "item_name": "RAM 8GB DDR4",
      "quantity": 1,
      "unit_price": 450000,
      "subtotal": 450000
    }
  ],
  "custom_costs": [
    {
      "name": "Transport Fee",
      "amount": 50000
    }
  ],
  "summary": {
    "service_cost": 200000,
    "spareparts_total": 450000,
    "custom_costs_total": 50000,
    "total_cost": 700000
  }
}
```

### 4. Add Sparepart to Order

**Endpoint:** `POST /staff/api/add-sparepart-to-order.php`

**Request:**
```json
{
  "order_id": 123,
  "item_id": 10,
  "quantity": 2,
  "notes": "Replaced broken RAM"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Sparepart added to order",
  "item": {
    "item_id": 10,
    "name": "RAM 8GB DDR4",
    "quantity": 2,
    "unit_price": 450000,
    "subtotal": 900000
  }
}
```

### 5. Update Service Cost

**Endpoint:** `POST /staff/api/update-service-cost.php`

**Request:**
```json
{
  "order_id": 123,
  "service_cost": 350000
}
```

### 6. Add Custom Cost

**Endpoint:** `POST /staff/api/add-custom-cost.php`

**Request:**
```json
{
  "order_id": 123,
  "cost_name": "Transport Fee",
  "cost_description": "Delivery to customer location",
  "cost_amount": 50000
}
```

---

## 🎨 YANG PERLU DITAMBAHKAN (Frontend UI)

### Modal-modal yang Perlu Dibuat:

1. **Member Check Modal**
   - Pertanyaan: "Apakah ada member?"
   - 2 tombol: "Yes, Search Member" | "No, Guest"

2. **Member Search Modal**
   - Search input
   - List hasil search
   - Tombol select member

3. **Guest Form Modal**
   - Input: Full Name, Phone, Email
   - Tombol: Create Order

4. **Create Order Form Modal**
   - Service Type dropdown
   - Device Type input
   - Brand, Model input
   - Problem Description textarea
   - Location dropdown
   - Priority dropdown
   - Tombol: Create Order

5. **Order Detail Modal (Main Feature)**
   - **Section 1: Order Info** (read-only)
   - **Section 2: Service Cost** (editable input)
   - **Section 3: Spareparts List**
     - Table showing spareparts
     - Button: + Add Sparepart
     - Button: Remove (per item)
   - **Section 4: Custom Costs**
     - Table showing custom costs
     - Button: + Add Custom Cost
     - Button: Remove (per cost)
   - **Section 5: Total Summary**
     - Service Cost: Rp xxx
     - Spareparts Total: Rp xxx
     - Custom Costs Total: Rp xxx
     - **TOTAL: Rp xxx** (bold, large)
   - Tombol: Save Changes

6. **Add Sparepart Modal**
   - Search inventory items
   - Show: Item Code, Name, Stock, Price
   - Input: Quantity
   - Input: Notes (optional)
   - Tombol: Add to Order

7. **Add Custom Cost Modal**
   - Input: Cost Name
   - Input: Description (optional)
   - Input: Amount
   - Tombol: Add Cost

---

## 📝 NEXT STEPS (Yang Perlu Anda Lakukan)

### Option A: Saya Lanjutkan Buat Frontend

Saya bisa melanjutkan membuat:
- HTML untuk semua modal
- JavaScript untuk handle semua interaksi
- CSS styling untuk modal
- Integration dengan API endpoints

**Estimasi:** 1-2 jam kerja

### Option B: Anda Integrasikan Sendiri

Jika Anda ingin mengintegrasikan sendiri:

1. Tambahkan modal HTML ke `orders.php`
2. Buat file `js/order-management.js`
3. Panggil API menggunakan `fetch()` atau `$.ajax()`
4. Update UI setelah dapat response

---

## 🔥 KEUNTUNGAN SYSTEM INI

1. ✅ **Real-time Inventory Sync**
   - Sparepart otomatis berkurang saat ditambahkan ke order
   - Stock langsung update di inventory page

2. ✅ **Flexible Cost Management**
   - Service cost, parts cost, custom costs terpisah
   - Mudah tracking biaya per jenis

3. ✅ **Guest & Member Support**
   - Guest otomatis jadi customer account
   - Bisa tracking history order per customer

4. ✅ **Low Stock Alert**
   - Otomatis notifikasi jika stock habis
   - Prevent over-selling

5. ✅ **Audit Trail**
   - Semua transaksi inventory tercatat
   - Bisa tracking siapa yang tambah/kurang sparepart

---

## 💡 TIPS IMPLEMENTASI

### Frontend JavaScript Pattern:

```javascript
// Example: Create Order
async function createOrder(orderData) {
    try {
        const response = await fetch('api/create-order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(orderData)
        });
        const result = await response.json();

        if (result.success) {
            alert('Order created: ' + result.order_number);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Network error');
    }
}

// Example: Add Sparepart
async function addSparepart(orderId, itemId, quantity) {
    const result = await fetch('api/add-sparepart-to-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            order_id: orderId,
            item_id: itemId,
            quantity: quantity
        })
    }).then(r => r.json());

    if (result.success) {
        // Refresh spareparts list
        loadOrderDetail(orderId);
    }
}
```

---

## 🎊 SUMMARY

**Backend: 100% READY** ✅
- 8 API endpoints fully functional
- Inventory auto-deduction implemented
- Low stock alerts working
- Transaction logging complete

**Frontend: NEED TO BUILD** ⏳
- Modals HTML (30 menit)
- JavaScript logic (1 jam)
- CSS styling (30 menit)

**Total Remaining Work:** ~2 jam

---

**Apakah Anda ingin saya lanjutkan membuat frontend (modal + JavaScript)?**

Atau Anda mau coba integrasikan sendiri menggunakan API yang sudah saya buat?

Kasih tahu saya dan saya akan lanjutkan! 💪
