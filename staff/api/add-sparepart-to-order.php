<?php
/**
 * API: Add Sparepart to Order
 * Add sparepart to order and auto-deduct from inventory
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/NotificationHelper.php';

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('edit_orders') || !hasPermission('record_inventory_transaction')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['order_id']) || !isset($data['item_id']) || !isset($data['quantity'])) {
        echo json_encode(['success' => false, 'message' => 'Order ID, item ID, and quantity required']);
        exit();
    }

    $order_id = intval($data['order_id']);
    $item_id = intval($data['item_id']);
    $quantity = intval($data['quantity']);
    $notes = $data['notes'] ?? '';

    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be positive']);
        exit();
    }

    $conn->beginTransaction();

    // Check if order exists
    $order_query = "SELECT order_number FROM orders WHERE order_id = :order_id";
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bindParam(':order_id', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get item details and check stock
    $item_query = "SELECT * FROM inventory_items WHERE item_id = :item_id";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bindParam(':item_id', $item_id);
    $item_stmt->execute();
    $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    if ($item['quantity'] < $quantity) {
        throw new Exception('Insufficient stock. Available: ' . $item['quantity']);
    }

    // Deduct from inventory
    $update_inventory = "UPDATE inventory_items
                        SET quantity = quantity - :quantity
                        WHERE item_id = :item_id";
    $update_stmt = $conn->prepare($update_inventory);
    $update_stmt->bindParam(':quantity', $quantity);
    $update_stmt->bindParam(':item_id', $item_id);
    $update_stmt->execute();

    // Record inventory transaction
    $transaction_notes = "Used for order {$order['order_number']}";
    if (!empty($notes)) {
        $transaction_notes .= " - {$notes}";
    }

    $insert_transaction = "INSERT INTO inventory_transactions (
                            item_id,
                            transaction_type,
                            quantity,
                            notes,
                            order_id,
                            created_by
                          ) VALUES (
                            :item_id,
                            'OUT',
                            :quantity,
                            :notes,
                            :order_id,
                            :created_by
                          )";

    $trans_stmt = $conn->prepare($insert_transaction);
    $trans_stmt->bindParam(':item_id', $item_id);
    $trans_stmt->bindParam(':quantity', $quantity);
    $trans_stmt->bindParam(':notes', $transaction_notes);
    $trans_stmt->bindParam(':order_id', $order_id);
    $trans_stmt->bindParam(':created_by', $_SESSION['user_id']);
    $trans_stmt->execute();

    // Update order costs
    $parts_cost_increase = $item['unit_price'] * $quantity;

    $update_costs = "UPDATE order_costs
                    SET parts_cost = parts_cost + :parts_cost,
                        total_cost = total_cost + :parts_cost
                    WHERE order_id = :order_id";

    $costs_stmt = $conn->prepare($update_costs);
    $costs_stmt->bindParam(':parts_cost', $parts_cost_increase);
    $costs_stmt->bindParam(':order_id', $order_id);
    $costs_stmt->execute();

    // Check if item is now low stock
    $new_quantity = $item['quantity'] - $quantity;
    if ($new_quantity <= $item['reorder_level']) {
        $notif = new NotificationHelper($conn);
        $notif->sendToRole('Owner', 'LOW_STOCK_ALERT',
            'Low Stock Alert',
            "{$item['name']} is now low in stock ({$new_quantity} {$item['unit']} remaining)",
            'inventory.php');
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sparepart added to order',
        'item' => [
            'item_id' => $item['item_id'],
            'name' => $item['name'],
            'quantity' => $quantity,
            'unit_price' => $item['unit_price'],
            'subtotal' => $parts_cost_increase
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
