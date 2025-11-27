<?php
/**
 * API: Add Custom Cost to Order
 * Add custom cost item (e.g., transport, admin fee, etc.)
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

    if (!isset($data['order_id']) || !isset($data['cost_name']) || !isset($data['cost_amount'])) {
        echo json_encode(['success' => false, 'message' => 'Order ID, cost name, and amount required']);
        exit();
    }

    $order_id = intval($data['order_id']);
    $cost_name = trim($data['cost_name']);
    $cost_amount = floatval($data['cost_amount']);
    $cost_description = $data['cost_description'] ?? '';

    if ($cost_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Cost amount must be positive']);
        exit();
    }

    $conn->beginTransaction();

    // Check if order exists
    $check_query = "SELECT order_id FROM orders WHERE order_id = :order_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':order_id', $order_id);
    $check_stmt->execute();

    if (!$check_stmt->fetch()) {
        throw new Exception('Order not found');
    }

    // Get current costs
    $cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
    $cost_stmt = $conn->prepare($cost_query);
    $cost_stmt->bindParam(':order_id', $order_id);
    $cost_stmt->execute();
    $current_costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);

    // Parse existing custom costs from notes
    $custom_costs = [];
    if (!empty($current_costs['notes'])) {
        $decoded = json_decode($current_costs['notes'], true);
        if (is_array($decoded) && isset($decoded['custom_costs'])) {
            $custom_costs = $decoded['custom_costs'];
        }
    }

    // Add new custom cost
    $new_custom_cost = [
        'id' => uniqid(),
        'name' => $cost_name,
        'description' => $cost_description,
        'amount' => $cost_amount,
        'added_at' => date('Y-m-d H:i:s'),
        'added_by' => $_SESSION['user_id']
    ];

    $custom_costs[] = $new_custom_cost;

    // Save back to notes as JSON
    $notes_data = ['custom_costs' => $custom_costs];
    $notes_json = json_encode($notes_data);

    // Update order costs
    $update_costs = "UPDATE order_costs
                    SET other_costs = other_costs + :cost_amount,
                        total_cost = total_cost + :cost_amount,
                        notes = :notes
                    WHERE order_id = :order_id";

    $update_stmt = $conn->prepare($update_costs);
    $update_stmt->bindParam(':cost_amount', $cost_amount);
    $update_stmt->bindParam(':notes', $notes_json);
    $update_stmt->bindParam(':order_id', $order_id);
    $update_stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Custom cost added',
        'custom_cost' => $new_custom_cost
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
