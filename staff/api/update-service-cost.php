<?php
/**
 * API: Update Service Cost
 * Update the service cost for an order
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
    echo json_encode(['success' => false, 'message' => 'No permission to edit orders']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['order_id']) || !isset($data['service_cost'])) {
        echo json_encode(['success' => false, 'message' => 'Order ID and service cost required']);
        exit();
    }

    $order_id = intval($data['order_id']);
    $service_cost = floatval($data['service_cost']);

    // Check if order exists
    $check_query = "SELECT order_id FROM orders WHERE order_id = :order_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':order_id', $order_id);
    $check_stmt->execute();

    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Get current costs
    $cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
    $cost_stmt = $conn->prepare($cost_query);
    $cost_stmt->bindParam(':order_id', $order_id);
    $cost_stmt->execute();
    $current_costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate new total
    $parts_cost = $current_costs['parts_cost'] ?? 0;
    $other_costs = $current_costs['other_costs'] ?? 0;
    $new_total = $service_cost + $parts_cost + $other_costs;

    // Update service cost
    $update_query = "UPDATE order_costs
                    SET service_cost = :service_cost,
                        total_cost = :total_cost
                    WHERE order_id = :order_id";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':service_cost', $service_cost);
    $update_stmt->bindParam(':total_cost', $new_total);
    $update_stmt->bindParam(':order_id', $order_id);
    $update_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Service cost updated',
        'new_total' => $new_total
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
