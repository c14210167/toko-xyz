<?php
/**
 * API: Search Inventory Items
 * Search inventory items to add as spareparts
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
if (!hasPermission('view_inventory')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    $where_conditions = ["1=1"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(name LIKE :search OR item_code LIKE :search2)";
        $params[':search'] = "%{$search}%";
        $params[':search2'] = "%{$search}%";
    }

    if ($category_id > 0) {
        $where_conditions[] = "category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get items with stock > 0
    $query = "SELECT
                ii.item_id,
                ii.item_code,
                ii.name,
                ii.description,
                ii.quantity,
                ii.unit,
                ii.unit_price,
                ic.category_name
              FROM inventory_items ii
              LEFT JOIN inventory_categories ic ON ii.category_id = ic.category_id
              WHERE {$where_clause}
              AND ii.quantity > 0
              ORDER BY ii.name
              LIMIT 50";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
