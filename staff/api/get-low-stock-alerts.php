<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check permission
if (!isset($_SESSION['user_id']) || !hasPermission('view_low_stock_alerts')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "SELECT i.*,
                     ic.category_name,
                     l.name as location_name,
                     (i.reorder_level - i.quantity) as needed_quantity
              FROM inventory_items i
              LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
              LEFT JOIN locations l ON i.location_id = l.location_id
              WHERE i.quantity <= i.reorder_level
              ORDER BY i.quantity ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
