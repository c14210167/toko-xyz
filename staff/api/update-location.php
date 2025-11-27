<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/PermissionManager.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check permission
if (!$permissionManager->hasAnyPermission(['manage_roles', 'manage_permissions'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$location_id = intval($input['location_id'] ?? 0);
$name = trim($input['name'] ?? '');
$address = trim($input['address'] ?? '');
$phone = trim($input['phone'] ?? '');

if ($location_id <= 0) {
    echo json_encode(['error' => 'Invalid location ID']);
    exit();
}

if (empty($name)) {
    echo json_encode(['error' => 'Location name is required']);
    exit();
}

try {
    // Check if location exists
    $check_query = "SELECT location_id FROM locations WHERE location_id = :location_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':location_id', $location_id);
    $check_stmt->execute();

    if (!$check_stmt->fetch()) {
        echo json_encode(['error' => 'Location not found']);
        exit();
    }

    // Check if new name conflicts with another location
    $conflict_query = "SELECT location_id FROM locations WHERE name = :name AND location_id != :location_id";
    $conflict_stmt = $conn->prepare($conflict_query);
    $conflict_stmt->bindParam(':name', $name);
    $conflict_stmt->bindParam(':location_id', $location_id);
    $conflict_stmt->execute();

    if ($conflict_stmt->fetch()) {
        echo json_encode(['error' => 'Location name already exists']);
        exit();
    }

    // Update location
    $update_query = "UPDATE locations SET name = :name, address = :address, phone = :phone WHERE location_id = :location_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':name', $name);
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':phone', $phone);
    $update_stmt->bindParam(':location_id', $location_id);
    $update_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
