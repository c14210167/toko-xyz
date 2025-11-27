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
$role_name = trim($input['role_name'] ?? '');
$description = trim($input['description'] ?? '');

if (empty($role_name)) {
    echo json_encode(['error' => 'Role name is required']);
    exit();
}

try {
    // Check if role name already exists
    $check_query = "SELECT role_id FROM roles WHERE role_name = :role_name";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':role_name', $role_name);
    $check_stmt->execute();

    if ($check_stmt->fetch()) {
        echo json_encode(['error' => 'Role name already exists']);
        exit();
    }

    // Check if description column exists
    $check_column = $conn->query("SHOW COLUMNS FROM roles LIKE 'description'");
    $has_description = $check_column->rowCount() > 0;

    // Insert new role
    if ($has_description) {
        $insert_query = "INSERT INTO roles (role_name, description, is_system_role) VALUES (:role_name, :description, 0)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':role_name', $role_name);
        $insert_stmt->bindParam(':description', $description);
    } else {
        $insert_query = "INSERT INTO roles (role_name, is_system_role) VALUES (:role_name, 0)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':role_name', $role_name);
    }
    $insert_stmt->execute();

    $new_role_id = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Role created successfully',
        'role_id' => $new_role_id
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
