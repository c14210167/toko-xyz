<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check permission
if (!isset($_SESSION['user_id']) || !hasPermission('create_inventory')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$name = $input['name'] ?? '';
$category_id = $input['category_id'] ?? null;
$description = $input['description'] ?? '';
$quantity = $input['quantity'] ?? 0;
$unit = $input['unit'] ?? 'pcs';
$unit_price = $input['unit_price'] ?? 0;
$reorder_level = $input['reorder_level'] ?? 10;
$location_id = $input['location_id'] ?? null;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Item name is required']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Generate item code
    $item_code = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

    $query = "INSERT INTO inventory_items
              (item_code, name, category_id, description, quantity, unit, unit_price, reorder_level, location_id)
              VALUES
              (:item_code, :name, :category_id, :description, :quantity, :unit, :unit_price, :reorder_level, :location_id)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':item_code', $item_code);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':unit', $unit);
    $stmt->bindParam(':unit_price', $unit_price);
    $stmt->bindParam(':reorder_level', $reorder_level);
    $stmt->bindParam(':location_id', $location_id);

    if ($stmt->execute()) {
        $item_id = $conn->lastInsertId();

        // Record initial stock if quantity > 0
        if ($quantity > 0) {
            $transQuery = "INSERT INTO inventory_transactions
                          (item_id, transaction_type, quantity, notes, created_by)
                          VALUES (:item_id, 'IN', :quantity, 'Initial stock', :created_by)";

            $transStmt = $conn->prepare($transQuery);
            $transStmt->bindParam(':item_id', $item_id);
            $transStmt->bindParam(':quantity', $quantity);
            $transStmt->bindParam(':created_by', $_SESSION['user_id']);
            $transStmt->execute();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Item added successfully',
            'item_id' => $item_id,
            'item_code' => $item_code
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
