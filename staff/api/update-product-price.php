<?php
/**
 * API: Update Product Price
 * Updates product selling price and unit price with activity logging
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/ActivityLogger.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('record_inventory_transaction')) {
    echo json_encode(['success' => false, 'message' => 'No permission to update prices']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($data['item_id']) || !isset($data['selling_price']) || !isset($data['unit_price']) || empty($data['notes'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $item_id = intval($data['item_id']);
    $new_selling_price = floatval($data['selling_price']);
    $new_unit_price = floatval($data['unit_price']); // Cost price
    $notes = trim($data['notes']);

    // Get current inventory item data
    $get_query = "SELECT item_id, name,
                  COALESCE(item_code, CONCAT('SKU-', item_id)) as sku,
                  selling_price, unit_price
                  FROM inventory_items WHERE item_id = :item_id";
    $get_stmt = $conn->prepare($get_query);
    $get_stmt->bindParam(':item_id', $item_id);
    $get_stmt->execute();
    $product = $get_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    $old_selling_price = floatval($product['selling_price']);
    $old_unit_price = floatval($product['unit_price']);

    // Check if prices actually changed
    if ($old_selling_price == $new_selling_price && $old_unit_price == $new_unit_price) {
        echo json_encode(['success' => false, 'message' => 'No price changes detected']);
        exit();
    }

    $conn->beginTransaction();

    // Update inventory item prices
    $update_query = "UPDATE inventory_items
                     SET selling_price = :selling_price,
                         unit_price = :unit_price,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE item_id = :item_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':selling_price', $new_selling_price);
    $update_stmt->bindParam(':unit_price', $new_unit_price);
    $update_stmt->bindParam(':item_id', $item_id);
    $update_stmt->execute();

    // Create detailed activity log message
    $activity_details = "Updated prices for: {$product['name']} (SKU: {$product['sku']})\n";

    if ($old_selling_price != $new_selling_price) {
        $activity_details .= "Selling Price: Rp " . number_format($old_selling_price, 0, ',', '.') .
                           " → Rp " . number_format($new_selling_price, 0, ',', '.') . "\n";
    }

    if ($old_unit_price != $new_unit_price) {
        $activity_details .= "Unit Price (Cost): Rp " . number_format($old_unit_price, 0, ',', '.') .
                           " → Rp " . number_format($new_unit_price, 0, ',', '.') . "\n";
    }

    $activity_details .= "Reason: {$notes}";

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('PRICE_UPDATE', $activity_details);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
        // Don't fail the whole operation if logging fails
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Price updated successfully',
        'data' => [
            'product_id' => $item_id,
            'old_selling_price' => $old_selling_price,
            'new_selling_price' => $new_selling_price,
            'old_unit_price' => $old_unit_price,
            'new_unit_price' => $new_unit_price
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Update Product Price Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update price: ' . $e->getMessage()
    ]);
}
?>
