<?php
/**
 * API: Remove Custom Cost from Order
 * Remove custom cost item from order
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

    if (!isset($data['order_id']) || !isset($data['cost_id'])) {
        echo json_encode(['success' => false, 'message' => 'Order ID and cost ID required']);
        exit();
    }

    $order_id = intval($data['order_id']);
    $cost_id = $data['cost_id'];

    $conn->beginTransaction();

    // Get current costs
    $cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
    $cost_stmt = $conn->prepare($cost_query);
    $cost_stmt->bindParam(':order_id', $order_id);
    $cost_stmt->execute();
    $current_costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_costs) {
        throw new Exception('Order not found');
    }

    // Parse custom costs
    $custom_costs = [];
    if (!empty($current_costs['notes'])) {
        $decoded = json_decode($current_costs['notes'], true);
        if (is_array($decoded) && isset($decoded['custom_costs'])) {
            $custom_costs = $decoded['custom_costs'];
        }
    }

    // Find and remove the cost
    $removed_amount = 0;
    $found = false;
    foreach ($custom_costs as $key => $cost) {
        if ($cost['id'] === $cost_id) {
            $removed_amount = $cost['amount'];
            unset($custom_costs[$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        throw new Exception('Custom cost not found');
    }

    // Re-index array
    $custom_costs = array_values($custom_costs);

    // Save back to notes
    $notes_data = ['custom_costs' => $custom_costs];
    $notes_json = json_encode($notes_data);

    // Update order costs
    $update_costs = "UPDATE order_costs
                    SET other_costs = other_costs - :cost_amount,
                        total_cost = total_cost - :cost_amount,
                        notes = :notes
                    WHERE order_id = :order_id";

    $update_stmt = $conn->prepare($update_costs);
    $update_stmt->bindParam(':cost_amount', $removed_amount);
    $update_stmt->bindParam(':notes', $notes_json);
    $update_stmt->bindParam(':order_id', $order_id);
    $update_stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Custom cost removed'
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
