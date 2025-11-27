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
$role_id = $input['role_id'] ?? null;

if (!$role_id) {
    echo json_encode(['error' => 'Role ID is required']);
    exit();
}

try {
    // Check if it's a system role
    $check_query = "SELECT is_system_role, role_name FROM roles WHERE role_id = :role_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':role_id', $role_id);
    $check_stmt->execute();
    $role = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        echo json_encode(['error' => 'Role not found']);
        exit();
    }

    if ($role['is_system_role']) {
        echo json_encode(['error' => 'Cannot delete system roles']);
        exit();
    }

    // Delete the role (cascades will handle user_roles and role_permissions)
    $delete_query = "DELETE FROM roles WHERE role_id = :role_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':role_id', $role_id);
    $delete_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Role deleted successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
