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
$name = trim($input['name'] ?? '');
$address = trim($input['address'] ?? '');
$phone = trim($input['phone'] ?? '');

if (empty($name)) {
    echo json_encode(['error' => 'Location name is required']);
    exit();
}

try {
    // Check if location name already exists
    $check_query = "SELECT location_id FROM locations WHERE name = :name";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':name', $name);
    $check_stmt->execute();

    if ($check_stmt->fetch()) {
        echo json_encode(['error' => 'Location name already exists']);
        exit();
    }

    // Insert new location
    $insert_query = "INSERT INTO locations (name, address, phone) VALUES (:name, :address, :phone)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':name', $name);
    $insert_stmt->bindParam(':address', $address);
    $insert_stmt->bindParam(':phone', $phone);
    $insert_stmt->execute();

    $new_location_id = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Location created successfully',
        'location_id' => $new_location_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
