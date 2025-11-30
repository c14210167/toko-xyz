<?php
/**
 * API: Update Order Warranty Status
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('edit_orders')) {
    echo json_encode(['success' => false, 'error' => 'No permission to edit orders']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
    $warranty_status = isset($data['warranty_status']) ? intval($data['warranty_status']) : 0;

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit();
    }

    // Update warranty status
    $update_query = "UPDATE orders SET warranty_status = :warranty_status WHERE order_id = :order_id";
    $stmt = $conn->prepare($update_query);
    $stmt->bindParam(':warranty_status', $warranty_status);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Warranty status updated successfully',
        'warranty_status' => $warranty_status
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
