<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_roles')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get all roles with permission count
    $query = "SELECT r.role_id, r.role_name, r.description, r.is_system_role,
                     COUNT(rp.permission_id) as permission_count,
                     COUNT(ur.user_id) as user_count
              FROM roles r
              LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
              LEFT JOIN user_roles ur ON r.role_id = ur.role_id
              GROUP BY r.role_id
              ORDER BY r.role_name";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
