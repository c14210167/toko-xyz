<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check permission
if (!hasPermission('view_inventory')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$supplier_id = $data['supplier_id'] ?? null;
$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$address = $data['address'] ?? '';

if (!$supplier_id || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Supplier ID and name are required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Check if supplier exists
    $check_query = "SELECT name FROM suppliers WHERE supplier_id = :supplier_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':supplier_id', $supplier_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        exit();
    }

    $old_supplier = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Check if new name conflicts with another supplier
    $conflict_query = "SELECT supplier_id FROM suppliers WHERE name = :name AND supplier_id != :supplier_id";
    $conflict_stmt = $conn->prepare($conflict_query);
    $conflict_stmt->bindParam(':name', $name);
    $conflict_stmt->bindParam(':supplier_id', $supplier_id);
    $conflict_stmt->execute();

    if ($conflict_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'A supplier with this name already exists']);
        exit();
    }

    // Update supplier
    $update_query = "UPDATE suppliers
                     SET name = :name,
                         description = :description,
                         address = :address,
                         updated_at = NOW()
                     WHERE supplier_id = :supplier_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':name', $name);
    $update_stmt->bindParam(':description', $description);
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':supplier_id', $supplier_id);
    $update_stmt->execute();

    // Log activity
    require_once '../../includes/ActivityLogger.php';
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('supplier_update', "Updated supplier: {$old_supplier['name']} → {$name}", 'supplier', $supplier_id);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Supplier updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
