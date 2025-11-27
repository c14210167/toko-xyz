<?php
/**
 * API: Get Order Detail
 * Get complete order details with spareparts and costs
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
if (!hasPermission('view_orders')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }

    // Get order details
    $order_query = "SELECT
                        o.*,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        u.email as customer_email,
                        u.phone as customer_phone,
                        u.address as customer_address,
                        l.name as location_name,
                        CONCAT(tech.first_name, ' ', tech.last_name) as technician_name
                    FROM orders o
                    JOIN users u ON o.user_id = u.user_id
                    LEFT JOIN locations l ON o.location_id = l.location_id
                    LEFT JOIN users tech ON o.technician_id = tech.user_id
                    WHERE o.order_id = :order_id";

    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bindParam(':order_id', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    // Get order costs
    $cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
    $cost_stmt = $conn->prepare($cost_query);
    $cost_stmt->bindParam(':order_id', $order_id);
    $cost_stmt->execute();
    $costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);

    // Get spareparts used in this order
    $spareparts_query = "SELECT
                            it.transaction_id,
                            ii.item_id,
                            ii.item_code,
                            ii.name as item_name,
                            it.quantity,
                            it.notes,
                            ii.unit_price,
                            (ii.unit_price * it.quantity) as subtotal,
                            it.created_at
                        FROM inventory_transactions it
                        JOIN inventory_items ii ON it.item_id = ii.item_id
                        WHERE it.order_id = :order_id
                        AND it.transaction_type = 'OUT'
                        ORDER BY it.created_at DESC";

    $spareparts_stmt = $conn->prepare($spareparts_query);
    $spareparts_stmt->bindParam(':order_id', $order_id);
    $spareparts_stmt->execute();
    $spareparts = $spareparts_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get custom cost items (stored as JSON in notes or separate table)
    // For now, we'll parse from order_costs notes if available
    $custom_costs = [];
    if (!empty($costs['notes'])) {
        $decoded = json_decode($costs['notes'], true);
        if (is_array($decoded) && isset($decoded['custom_costs'])) {
            $custom_costs = $decoded['custom_costs'];
        }
    }

    // Calculate totals
    $spareparts_total = array_sum(array_column($spareparts, 'subtotal'));
    $service_cost = $costs['service_cost'] ?? 0;
    $custom_costs_total = array_sum(array_column($custom_costs, 'amount'));
    $total_cost = $service_cost + $spareparts_total + $custom_costs_total;

    echo json_encode([
        'success' => true,
        'order' => $order,
        'costs' => $costs,
        'spareparts' => $spareparts,
        'custom_costs' => $custom_costs,
        'summary' => [
            'service_cost' => $service_cost,
            'spareparts_total' => $spareparts_total,
            'custom_costs_total' => $custom_costs_total,
            'total_cost' => $total_cost
        ]
    ]);

} catch (PDOException $e) {
    // Log error for debugging
    error_log("Order Detail Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_detail' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
} catch (Exception $e) {
    error_log("Order Detail Exception: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
