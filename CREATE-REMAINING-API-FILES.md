# 📁 SCRIPT UNTUK MEMBUAT SEMUA API FILES

Jalankan script PHP ini untuk membuat semua file API yang masih kurang.

## Option 1: Manual Copy-Paste

Copy kode di bawah ini dan buat file sesuai path nya.

---

## PRODUCTS API

### staff/api/get-products.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('view_products')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $active = $_GET['active'] ?? '1';

    $query = "SELECT p.*, pc.category_name
              FROM products p
              LEFT JOIN product_categories pc ON p.category_id = pc.category_id
              WHERE 1=1";

    $params = [];

    if ($search) {
        $query .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($category) {
        $query .= " AND p.category_id = :category";
        $params[':category'] = $category;
    }

    if ($active !== '') {
        $query .= " AND p.is_active = :active";
        $params[':active'] = $active;
    }

    $query .= " ORDER BY p.name ASC";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

### staff/api/add-product.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('manage_products')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sku = $input['sku'] ?? 'PRD-' . date('Ymd') . '-' . rand(1000, 9999);

    $query = "INSERT INTO products (sku, name, category_id, brand, description, cost_price, selling_price, quantity, reorder_level)
              VALUES (:sku, :name, :category_id, :brand, :description, :cost_price, :selling_price, :quantity, :reorder_level)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sku', $sku);
    $stmt->bindParam(':name', $input['name']);
    $stmt->bindParam(':category_id', $input['category_id']);
    $stmt->bindParam(':brand', $input['brand']);
    $stmt->bindParam(':description', $input['description']);
    $stmt->bindParam(':cost_price', $input['cost_price']);
    $stmt->bindParam(':selling_price', $input['selling_price']);
    $stmt->bindParam(':quantity', $input['quantity']);
    $stmt->bindParam(':reorder_level', $input['reorder_level']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'product_id' => $conn->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

### staff/api/create-sale.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id']) || !hasPermission('create_sale')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->beginTransaction();

    // Generate sale number
    $sale_number = 'SALE-' . date('Ymd') . '-' . rand(10000, 99999);

    // Insert sale
    $query = "INSERT INTO sales (sale_number, location_id, customer_id, subtotal, discount_amount, tax_amount, total_amount, payment_method, created_by)
              VALUES (:sale_number, :location_id, :customer_id, :subtotal, :discount, :tax, :total, :payment_method, :created_by)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':sale_number', $sale_number);
    $stmt->bindParam(':location_id', $input['location_id']);
    $stmt->bindParam(':customer_id', $input['customer_id']);
    $stmt->bindParam(':subtotal', $input['subtotal']);
    $stmt->bindParam(':discount', $input['discount_amount']);
    $stmt->bindParam(':tax', $input['tax_amount']);
    $stmt->bindParam(':total', $input['total_amount']);
    $stmt->bindParam(':payment_method', $input['payment_method']);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    $stmt->execute();

    $sale_id = $conn->lastInsertId();

    // Insert sale items & update stock
    foreach ($input['items'] as $item) {
        // Insert sale item
        $query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, discount, subtotal)
                  VALUES (:sale_id, :product_id, :quantity, :unit_price, :discount, :subtotal)";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':sale_id', $sale_id);
        $stmt->bindParam(':product_id', $item['product_id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':unit_price', $item['unit_price']);
        $stmt->bindParam(':discount', $item['discount']);
        $stmt->bindParam(':subtotal', $item['subtotal']);
        $stmt->execute();

        // Update stock
        $query = "UPDATE products SET quantity = quantity - :quantity WHERE product_id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':product_id', $item['product_id']);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'sale_id' => $sale_id, 'sale_number' => $sale_number]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## EXPENSES API

### staff/api/get-expenses.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('view_expenses')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-t');
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';

    $query = "SELECT e.*, ec.category_name, l.name as location_name,
                     CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM expenses e
              LEFT JOIN expense_categories ec ON e.category_id = ec.category_id
              LEFT JOIN locations l ON e.location_id = l.location_id
              LEFT JOIN users u ON e.created_by = u.user_id
              WHERE e.expense_date BETWEEN :from AND :to";

    $params = [':from' => $from, ':to' => $to];

    if ($category) {
        $query .= " AND e.category_id = :category";
        $params[':category'] = $category;
    }

    if ($status) {
        $query .= " AND e.status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY e.expense_date DESC";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'expenses' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

### staff/api/add-expense.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id']) || !hasPermission('create_expense')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $expense_number = 'EXP-' . date('Ymd') . '-' . rand(1000, 9999);

    $query = "INSERT INTO expenses (expense_number, location_id, category_id, title, description, amount, expense_date, status, created_by)
              VALUES (:expense_number, :location_id, :category_id, :title, :description, :amount, :expense_date, :status, :created_by)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':expense_number', $expense_number);
    $stmt->bindParam(':location_id', $input['location_id']);
    $stmt->bindParam(':category_id', $input['category_id']);
    $stmt->bindParam(':title', $input['title']);
    $stmt->bindParam(':description', $input['description']);
    $stmt->bindParam(':amount', $input['amount']);
    $stmt->bindParam(':expense_date', $input['expense_date']);
    $stmt->bindParam(':status', $input['status']);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);

    if ($stmt->execute()) {
        $expense_id = $conn->lastInsertId();

        // Notify manager/owner if approval needed
        if ($input['status'] === 'Pending') {
            NotificationHelper::sendToRole(
                'Manager',
                NotificationHelper::EXPENSE_APPROVAL_NEEDED,
                'Expense Approval Needed',
                $input['title'] . ' - ' . number_format($input['amount'], 0, ',', '.'),
                '/staff/expenses.php',
                NotificationHelper::getIcon(NotificationHelper::EXPENSE_APPROVAL_NEEDED)
            );
        }

        echo json_encode(['success' => true, 'expense_id' => $expense_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

### staff/api/approve-expense.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('approve_expense')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "UPDATE expenses
              SET status = :status, approved_by = :approved_by, approved_at = NOW()
              WHERE expense_id = :expense_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $input['status']); // Approved or Rejected
    $stmt->bindParam(':approved_by', $_SESSION['user_id']);
    $stmt->bindParam(':expense_id', $input['expense_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## PAYMENTS API

### staff/api/record-payment.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id']) || !hasPermission('record_payment')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $payment_number = 'PAY-' . date('Ymd') . '-' . rand(10000, 99999);

    $query = "INSERT INTO payments (payment_number, order_id, amount, payment_method, payment_status, transaction_id, notes, created_by)
              VALUES (:payment_number, :order_id, :amount, :payment_method, :payment_status, :transaction_id, :notes, :created_by)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':payment_number', $payment_number);
    $stmt->bindParam(':order_id', $input['order_id']);
    $stmt->bindParam(':amount', $input['amount']);
    $stmt->bindParam(':payment_method', $input['payment_method']);
    $stmt->bindParam(':payment_status', $input['payment_status']);
    $stmt->bindParam(':transaction_id', $input['transaction_id']);
    $stmt->bindParam(':notes', $input['notes']);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);

    if ($stmt->execute()) {
        $payment_id = $conn->lastInsertId();

        // Get customer ID from order
        $query = "SELECT user_id FROM orders WHERE order_id = :order_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_id', $input['order_id']);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Notify customer
        NotificationHelper::send(
            $order['user_id'],
            NotificationHelper::PAYMENT_RECEIVED,
            'Payment Received',
            'Payment of Rp ' . number_format($input['amount'], 0, ',', '.') . ' has been received',
            '/customer/orders.php',
            NotificationHelper::getIcon(NotificationHelper::PAYMENT_RECEIVED)
        );

        echo json_encode(['success' => true, 'payment_id' => $payment_id, 'payment_number' => $payment_number]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## APPOINTMENTS API

### customer/api/book-appointment.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $appointment_number = 'APT-' . date('Ymd') . '-' . rand(1000, 9999);

    $query = "INSERT INTO appointments (appointment_number, customer_id, location_id, appointment_date, time_slot, device_type, issue_description, status)
              VALUES (:appointment_number, :customer_id, :location_id, :appointment_date, :time_slot, :device_type, :issue_description, 'Pending')";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':appointment_number', $appointment_number);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->bindParam(':location_id', $input['location_id']);
    $stmt->bindParam(':appointment_date', $input['appointment_date']);
    $stmt->bindParam(':time_slot', $input['time_slot']);
    $stmt->bindParam(':device_type', $input['device_type']);
    $stmt->bindParam(':issue_description', $input['issue_description']);

    if ($stmt->execute()) {
        $appointment_id = $conn->lastInsertId();

        // Notify staff
        NotificationHelper::sendToRole(
            'Manager',
            NotificationHelper::APPOINTMENT_BOOKED,
            'New Appointment',
            'Appointment for ' . $input['appointment_date'] . ' at ' . $input['time_slot'],
            '/staff/appointments.php',
            NotificationHelper::getIcon(NotificationHelper::APPOINTMENT_BOOKED)
        );

        echo json_encode(['success' => true, 'appointment_id' => $appointment_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

### staff/api/update-appointment-status.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id']) || !hasPermission('manage_appointments')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "UPDATE appointments
              SET status = :status, approved_by = :approved_by
              WHERE appointment_id = :appointment_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $input['status']);
    $stmt->bindParam(':approved_by', $_SESSION['user_id']);
    $stmt->bindParam(':appointment_id', $input['appointment_id']);

    if ($stmt->execute()) {
        // Get customer ID
        $query = "SELECT customer_id FROM appointments WHERE appointment_id = :appointment_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':appointment_id', $input['appointment_id']);
        $stmt->execute();
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Notify customer
        $notif_type = $input['status'] === 'Confirmed' ? NotificationHelper::APPOINTMENT_APPROVED : NotificationHelper::APPOINTMENT_REJECTED;
        NotificationHelper::send(
            $appointment['customer_id'],
            $notif_type,
            'Appointment ' . $input['status'],
            'Your appointment has been ' . strtolower($input['status']),
            '/customer/appointments.php',
            NotificationHelper::getIcon($notif_type)
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## RATINGS API

### customer/api/submit-rating.php
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Calculate overall rating
    $overall = ($input['service_rating'] + $input['technician_rating'] + $input['speed_rating'] + $input['price_rating']) / 4;

    $query = "INSERT INTO ratings (order_id, customer_id, technician_id, service_rating, technician_rating, speed_rating, price_rating, overall_rating, feedback)
              VALUES (:order_id, :customer_id, :technician_id, :service_rating, :technician_rating, :speed_rating, :price_rating, :overall_rating, :feedback)
              ON DUPLICATE KEY UPDATE
                service_rating = :service_rating,
                technician_rating = :technician_rating,
                speed_rating = :speed_rating,
                price_rating = :price_rating,
                overall_rating = :overall_rating,
                feedback = :feedback";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $input['order_id']);
    $stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $stmt->bindParam(':technician_id', $input['technician_id']);
    $stmt->bindParam(':service_rating', $input['service_rating']);
    $stmt->bindParam(':technician_rating', $input['technician_rating']);
    $stmt->bindParam(':speed_rating', $input['speed_rating']);
    $stmt->bindParam(':price_rating', $input['price_rating']);
    $stmt->bindParam(':overall_rating', $overall);
    $stmt->bindParam(':feedback', $input['feedback']);

    if ($stmt->execute()) {
        // Notify staff
        NotificationHelper::sendToRole(
            'Manager',
            NotificationHelper::NEW_RATING,
            'New Rating Received',
            'Order #' . $input['order_id'] . ' rated ' . $overall . ' stars',
            '/staff/ratings.php',
            NotificationHelper::getIcon(NotificationHelper::NEW_RATING)
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## Option 2: Auto-Generate Script

Buat file `generate-api-files.php` dan jalankan sekali:

```php
<?php
// This script will create all API files automatically
// Run once: http://localhost/frontendproject/generate-api-files.php

$files = [
    'staff/api/get-products.php' => '<?php /* paste code from above */',
    'staff/api/add-product.php' => '<?php /* paste code from above */',
    // ... etc
];

foreach ($files as $path => $content) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $content);
    echo "Created: $path<br>";
}

echo "<br><strong>All API files created!</strong>";
?>
```

---

Semua API endpoint di atas sudah include:
- ✅ Permission check
- ✅ Error handling
- ✅ Notification support
- ✅ Transaction support (where needed)

Tinggal buat UI nya saja!
