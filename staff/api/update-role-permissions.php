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
$permission_ids = $input['permission_ids'] ?? [];

if (!$role_id) {
    echo json_encode(['error' => 'Role ID is required']);
    exit();
}

try {
    $conn->beginTransaction();

    // Delete existing permissions
    $delete_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':role_id', $role_id);
    $delete_stmt->execute();

    // Insert new permissions
    if (!empty($permission_ids)) {
        $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
        $insert_stmt = $conn->prepare($insert_query);

        foreach ($permission_ids as $permission_id) {
            $insert_stmt->bindParam(':role_id', $role_id);
            $insert_stmt->bindParam(':permission_id', $permission_id);
            $insert_stmt->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Role permissions updated successfully'
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
