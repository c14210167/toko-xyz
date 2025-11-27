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

$role_id = $_GET['role_id'] ?? null;

if (!$role_id) {
    echo json_encode(['error' => 'Role ID is required']);
    exit();
}

try {
    // Check if description column exists in permissions table
    $check_perm_desc = $conn->query("SHOW COLUMNS FROM permissions LIKE 'description'");
    $has_perm_description = $check_perm_desc->rowCount() > 0;

    // Get all permissions grouped by category
    if ($has_perm_description) {
        $permissions_query = "SELECT
                                permission_id,
                                permission_key,
                                permission_name,
                                description,
                                category
                              FROM permissions
                              ORDER BY category, permission_name";
    } else {
        $permissions_query = "SELECT
                                permission_id,
                                permission_key,
                                permission_name,
                                '' as description,
                                category
                              FROM permissions
                              ORDER BY category, permission_name";
    }

    $permissions_stmt = $conn->prepare($permissions_query);
    $permissions_stmt->execute();
    $all_permissions = $permissions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current role permissions
    $role_permissions_query = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
    $role_permissions_stmt = $conn->prepare($role_permissions_query);
    $role_permissions_stmt->bindParam(':role_id', $role_id);
    $role_permissions_stmt->execute();
    $role_permission_ids = $role_permissions_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Group permissions by category and mark assigned ones
    $grouped_permissions = [];
    foreach ($all_permissions as $perm) {
        $category = $perm['category'] ?? 'other';
        if (!isset($grouped_permissions[$category])) {
            $grouped_permissions[$category] = [];
        }
        $perm['is_assigned'] = in_array($perm['permission_id'], $role_permission_ids);
        $grouped_permissions[$category][] = $perm;
    }

    echo json_encode([
        'success' => true,
        'permissions' => $grouped_permissions
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
