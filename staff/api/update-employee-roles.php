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
$employee_id = $input['employee_id'] ?? null;
$role_ids = $input['role_ids'] ?? [];

if (!$employee_id) {
    echo json_encode(['error' => 'Employee ID is required']);
    exit();
}

try {
    $conn->beginTransaction();

    // Delete existing roles
    $delete_query = "DELETE FROM user_roles WHERE user_id = :user_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':user_id', $employee_id);
    $delete_stmt->execute();

    // Insert new roles
    if (!empty($role_ids)) {
        $insert_query = "INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)";
        $insert_stmt = $conn->prepare($insert_query);

        foreach ($role_ids as $role_id) {
            $insert_stmt->bindParam(':user_id', $employee_id);
            $insert_stmt->bindParam(':role_id', $role_id);
            $insert_stmt->bindParam(':assigned_by', $_SESSION['user_id']);
            $insert_stmt->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Employee roles updated successfully'
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
