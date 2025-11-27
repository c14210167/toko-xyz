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

$database = new Database();
$conn = $database->getConnection();

try {
    // Check if requesting a specific supplier
    $supplier_id = $_GET['supplier_id'] ?? null;

    if ($supplier_id) {
        // Get single supplier
        $query = "SELECT * FROM suppliers WHERE supplier_id = :supplier_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':supplier_id', $supplier_id, PDO::PARAM_INT);
        $stmt->execute();
        $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($supplier) {
            echo json_encode([
                'success' => true,
                'supplier' => $supplier
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Supplier not found'
            ]);
        }
    } else {
        // Get all suppliers
        $query = "SELECT * FROM suppliers ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
