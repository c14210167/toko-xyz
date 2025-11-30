<?php
/**
 * API: Get Products for POS
 * Returns available products for sale in POS
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasAnyPermission(['access_pos', 'create_transaction', 'view_inventory'])) {
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Get products from inventory_items with stock information
    $query = "SELECT
                i.item_id as product_id,
                i.name as product_name,
                COALESCE(i.item_code, CONCAT('SKU-', i.item_id)) as sku,
                COALESCE(c.category_name, 'Uncategorized') as category,
                COALESCE(i.selling_price, ROUND(i.unit_price * 1.3, 2)) as selling_price,
                i.quantity as stock_quantity,
                CASE
                    WHEN i.quantity > 10 THEN 'In Stock'
                    WHEN i.quantity > 0 THEN 'Low Stock'
                    ELSE 'Out of Stock'
                END as stock_status
              FROM inventory_items i
              LEFT JOIN inventory_categories c ON i.category_id = c.category_id
              WHERE i.quantity > 0";

    // Add search filter if provided
    if (!empty($search)) {
        $query .= " AND (i.name LIKE :search OR i.item_code LIKE :search OR c.category_name LIKE :search)";
    }

    $query .= " ORDER BY i.name ASC";

    $stmt = $conn->prepare($query);

    if (!empty($search)) {
        $search_term = "%$search%";
        $stmt->bindParam(':search', $search_term);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format products
    $formatted_products = array_map(function($product) {
        return [
            'product_id' => intval($product['product_id']),
            'product_name' => $product['product_name'],
            'sku' => $product['sku'],
            'category' => $product['category'],
            'price' => floatval($product['selling_price']),
            'stock' => intval($product['stock_quantity']),
            'stock_status' => $product['stock_status']
        ];
    }, $products);

    echo json_encode([
        'success' => true,
        'products' => $formatted_products
    ]);

} catch (PDOException $e) {
    error_log("Get POS Products Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
