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

$employee_id = $_GET['id'] ?? null;

if (!$employee_id) {
    echo json_encode(['error' => 'Employee ID is required']);
    exit();
}

try {
    // Check if description column exists
    $check_column = $conn->query("SHOW COLUMNS FROM roles LIKE 'description'");
    $has_description = $check_column->rowCount() > 0;

    // Build query based on column existence
    if ($has_description) {
        $roles_query = "SELECT r.role_id, r.role_name, r.description, r.is_system_role
                       FROM user_roles ur
                       JOIN roles r ON ur.role_id = r.role_id
                       WHERE ur.user_id = :user_id
                       ORDER BY r.role_name";
    } else {
        $roles_query = "SELECT r.role_id, r.role_name, '' as description, r.is_system_role
                       FROM user_roles ur
                       JOIN roles r ON ur.role_id = r.role_id
                       WHERE ur.user_id = :user_id
                       ORDER BY r.role_name";
    }

    $roles_stmt = $conn->prepare($roles_query);
    $roles_stmt->bindParam(':user_id', $employee_id);
    $roles_stmt->execute();
    $current_roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all available roles
    if ($has_description) {
        $all_roles_query = "SELECT role_id, role_name, description, is_system_role FROM roles ORDER BY role_name";
    } else {
        $all_roles_query = "SELECT role_id, role_name, '' as description, is_system_role FROM roles ORDER BY role_name";
    }
    $all_roles_stmt = $conn->prepare($all_roles_query);
    $all_roles_stmt->execute();
    $all_roles = $all_roles_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark which roles are assigned
    $current_role_ids = array_column($current_roles, 'role_id');
    $all_roles_with_status = array_map(function($role) use ($current_role_ids) {
        return [
            'role_id' => $role['role_id'],
            'role_name' => $role['role_name'],
            'description' => $role['description'],
            'is_system_role' => $role['is_system_role'],
            'is_assigned' => in_array($role['role_id'], $current_role_ids)
        ];
    }, $all_roles);

    echo json_encode([
        'success' => true,
        'current_roles' => $current_roles,
        'all_roles' => $all_roles_with_status
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
