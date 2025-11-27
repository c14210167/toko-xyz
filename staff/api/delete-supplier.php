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

if (!$supplier_id) {
    echo json_encode(['success' => false, 'message' => 'Supplier ID is required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Get supplier info before deleting
    $check_query = "SELECT name FROM suppliers WHERE supplier_id = :supplier_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':supplier_id', $supplier_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
        exit();
    }

    $supplier = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Delete supplier
    $delete_query = "DELETE FROM suppliers WHERE supplier_id = :supplier_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':supplier_id', $supplier_id);
    $delete_stmt->execute();

    // Log activity
    require_once '../../includes/ActivityLogger.php';
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('supplier_delete', "Deleted supplier: {$supplier['name']}", 'supplier', $supplier_id);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Supplier deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
