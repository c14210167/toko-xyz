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

try {
    // Check if description column exists
    $check_column = $conn->query("SHOW COLUMNS FROM roles LIKE 'description'");
    $has_description = $check_column->rowCount() > 0;

    // Get all roles with user count
    if ($has_description) {
        $query = "SELECT
                    r.role_id,
                    r.role_name,
                    r.description,
                    r.is_system_role,
                    r.created_at,
                    COUNT(DISTINCT ur.user_id) as user_count,
                    COUNT(DISTINCT rp.permission_id) as permission_count
                  FROM roles r
                  LEFT JOIN user_roles ur ON r.role_id = ur.role_id
                  LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
                  GROUP BY r.role_id
                  ORDER BY r.is_system_role DESC, r.role_name";
    } else {
        $query = "SELECT
                    r.role_id,
                    r.role_name,
                    '' as description,
                    r.is_system_role,
                    r.created_at,
                    COUNT(DISTINCT ur.user_id) as user_count,
                    COUNT(DISTINCT rp.permission_id) as permission_count
                  FROM roles r
                  LEFT JOIN user_roles ur ON r.role_id = ur.role_id
                  LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
                  GROUP BY r.role_id
                  ORDER BY r.is_system_role DESC, r.role_name";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
