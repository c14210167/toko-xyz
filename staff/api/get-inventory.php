<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check permission
if (!isset($_SESSION['user_id']) || !hasPermission('view_inventory')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $category = $_GET['category'] ?? '';
    $location = $_GET['location'] ?? '';
    $lowStock = $_GET['low_stock'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    // Build query
    $query = "SELECT i.*,
                     ic.category_name,
                     l.name as location_name,
                     CASE WHEN i.quantity <= i.reorder_level THEN 1 ELSE 0 END as is_low_stock
              FROM inventory_items i
              LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
              LEFT JOIN locations l ON i.location_id = l.location_id
              WHERE 1=1";

    $params = [];

    if ($category) {
        $query .= " AND i.category_id = :category";
        $params[':category'] = $category;
    }

    if ($location) {
        $query .= " AND i.location_id = :location";
        $params[':location'] = $location;
    }

    if ($lowStock === 'true') {
        $query .= " AND i.quantity <= i.reorder_level";
    }

    if ($search) {
        $query .= " AND (i.name LIKE :search OR i.item_code LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as counted";
    $countStmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get items with pagination
    $query .= " ORDER BY i.name ASC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => (int)$total,
        'page' => $page,
        'totalPages' => ceil($total / $limit)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
