<?php
/**
 * API: Remove Sparepart from Order
 * Remove sparepart from order and return to inventory
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('edit_orders')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['transaction_id'])) {
        echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
        exit();
    }

    $transaction_id = intval($data['transaction_id']);

    $conn->beginTransaction();

    // Get transaction details
    $trans_query = "SELECT * FROM inventory_transactions WHERE transaction_id = :transaction_id AND transaction_type = 'OUT'";
    $trans_stmt = $conn->prepare($trans_query);
    $trans_stmt->bindParam(':transaction_id', $transaction_id);
    $trans_stmt->execute();
    $transaction = $trans_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction not found');
    }

    // Get item details
    $item_query = "SELECT * FROM inventory_items WHERE item_id = :item_id";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bindParam(':item_id', $transaction['item_id']);
    $item_stmt->execute();
    $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    // Return to inventory
    $update_inventory = "UPDATE inventory_items
                        SET quantity = quantity + :quantity
                        WHERE item_id = :item_id";
    $update_stmt = $conn->prepare($update_inventory);
    $update_stmt->bindParam(':quantity', $transaction['quantity']);
    $update_stmt->bindParam(':item_id', $transaction['item_id']);
    $update_stmt->execute();

    // Create return transaction
    $return_notes = "Returned from order (removed sparepart)";
    $insert_return = "INSERT INTO inventory_transactions (
                        item_id,
                        transaction_type,
                        quantity,
                        notes,
                        created_by
                      ) VALUES (
                        :item_id,
                        'IN',
                        :quantity,
                        :notes,
                        :created_by
                      )";

    $return_stmt = $conn->prepare($insert_return);
    $return_stmt->bindParam(':item_id', $transaction['item_id']);
    $return_stmt->bindParam(':quantity', $transaction['quantity']);
    $return_stmt->bindParam(':notes', $return_notes);
    $return_stmt->bindParam(':created_by', $_SESSION['user_id']);
    $return_stmt->execute();

    // Update order costs
    $parts_cost_decrease = $item['unit_price'] * $transaction['quantity'];

    $update_costs = "UPDATE order_costs
                    SET parts_cost = parts_cost - :parts_cost,
                        total_cost = total_cost - :parts_cost
                    WHERE order_id = :order_id";

    $costs_stmt = $conn->prepare($update_costs);
    $costs_stmt->bindParam(':parts_cost', $parts_cost_decrease);
    $costs_stmt->bindParam(':order_id', $transaction['order_id']);
    $costs_stmt->execute();

    // Delete the original OUT transaction
    $delete_trans = "DELETE FROM inventory_transactions WHERE transaction_id = :transaction_id";
    $delete_stmt = $conn->prepare($delete_trans);
    $delete_stmt->bindParam(':transaction_id', $transaction_id);
    $delete_stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sparepart removed and returned to inventory'
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
