<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check permission
if (!isset($_SESSION['user_id']) || !hasPermission('record_inventory_transaction')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$item_id = $input['item_id'] ?? null;
$transaction_type = $input['transaction_type'] ?? ''; // IN, OUT, ADJUSTMENT
$quantity = $input['quantity'] ?? 0;
$notes = $input['notes'] ?? '';
$order_id = $input['order_id'] ?? null;

if (!$item_id || !$transaction_type || $quantity == 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

if (!in_array($transaction_type, ['IN', 'OUT', 'ADJUSTMENT'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->beginTransaction();

    // Get current quantity
    $query = "SELECT quantity FROM inventory_items WHERE item_id = :item_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit();
    }

    $current_quantity = $item['quantity'];
    $new_quantity = $current_quantity;

    // Calculate new quantity
    if ($transaction_type === 'IN') {
        $new_quantity = $current_quantity + $quantity;
    } elseif ($transaction_type === 'OUT') {
        $new_quantity = $current_quantity - $quantity;
        if ($new_quantity < 0) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            $conn->rollBack();
            exit();
        }
    } elseif ($transaction_type === 'ADJUSTMENT') {
        $new_quantity = $quantity; // Direct set
        $quantity = $quantity - $current_quantity; // Calculate difference
    }

    // Record transaction
    $query = "INSERT INTO inventory_transactions
              (item_id, transaction_type, quantity, notes, order_id, created_by)
              VALUES (:item_id, :transaction_type, :quantity, :notes, :order_id, :created_by)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':transaction_type', $transaction_type);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    $stmt->execute();

    // Update item quantity
    $query = "UPDATE inventory_items SET quantity = :quantity WHERE item_id = :item_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quantity', $new_quantity);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();

    $conn->commit();

    // Check if low stock and send notification
    $query = "SELECT name, quantity, reorder_level FROM inventory_items WHERE item_id = :item_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    $updated_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($updated_item['quantity'] <= $updated_item['reorder_level']) {
        require_once '../../includes/NotificationHelper.php';
        NotificationHelper::sendToRole(
            'Owner',
            NotificationHelper::LOW_STOCK_ALERT,
            'Low Stock Alert',
            $updated_item['name'] . ' is running low (Current: ' . $updated_item['quantity'] . ', Reorder Level: ' . $updated_item['reorder_level'] . ')',
            '/staff/inventory.php',
            NotificationHelper::getIcon(NotificationHelper::LOW_STOCK_ALERT)
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction recorded successfully',
        'new_quantity' => $new_quantity
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
