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
$name = $data['name'] ?? '';
$description = $data['description'] ?? '';
$address = $data['address'] ?? '';

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Check if supplier name already exists
    $check_query = "SELECT supplier_id FROM suppliers WHERE name = :name";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':name', $name);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'A supplier with this name already exists']);
        exit();
    }

    // Insert new supplier
    $insert_query = "INSERT INTO suppliers (name, description, address, created_at)
                     VALUES (:name, :description, :address, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':name', $name);
    $insert_stmt->bindParam(':description', $description);
    $insert_stmt->bindParam(':address', $address);
    $insert_stmt->execute();

    $supplier_id = $conn->lastInsertId();

    // Log activity
    require_once '../../includes/ActivityLogger.php';
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('supplier_create', "Created new supplier: {$name}", 'supplier', $supplier_id);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Supplier created successfully',
        'supplier_id' => $supplier_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
