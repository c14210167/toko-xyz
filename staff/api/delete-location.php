<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/PermissionManager.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permission
if (!$permissionManager->hasAnyPermission(['manage_roles', 'manage_permissions'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$location_id = intval($input['location_id'] ?? 0);

if ($location_id <= 0) {
    echo json_encode(['error' => 'Invalid location ID']);
    exit();
}

try {
    // Check if location has any orders
    $order_check = "SELECT COUNT(*) as order_count FROM orders WHERE location_id = :location_id";
    $order_stmt = $conn->prepare($order_check);
    $order_stmt->bindParam(':location_id', $location_id);
    $order_stmt->execute();
    $result = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['order_count'] > 0) {
        echo json_encode([
            'error' => 'Cannot delete location with existing orders. Please reassign orders first.',
            'order_count' => $result['order_count']
        ]);
        exit();
    }

    // Delete location
    $delete_query = "DELETE FROM locations WHERE location_id = :location_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':location_id', $location_id);
    $delete_stmt->execute();

    if ($delete_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Location deleted successfully'
        ]);
    } else {
        echo json_encode(['error' => 'Location not found']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
