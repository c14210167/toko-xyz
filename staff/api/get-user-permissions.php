<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('manage_permissions')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get user's roles
    $query = "SELECT r.role_id, r.role_name, r.description
              FROM roles r
              INNER JOIN user_roles ur ON r.role_id = ur.role_id
              WHERE ur.user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get permissions from roles
    $query = "SELECT DISTINCT p.permission_id, p.permission_key, p.permission_name, p.category,
                     'role' as source
              FROM permissions p
              INNER JOIN role_permissions rp ON p.permission_id = rp.permission_id
              INNER JOIN user_roles ur ON rp.role_id = ur.role_id
              WHERE ur.user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user-specific permission overrides
    $query = "SELECT p.permission_id, p.permission_key, p.permission_name, p.category,
                     up.is_granted,
                     IF(up.is_granted = 1, 'granted', 'revoked') as source
              FROM permissions p
              INNER JOIN user_permissions up ON p.permission_id = up.permission_id
              WHERE up.user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $userPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge permissions
    $allPermissions = [];
    foreach ($rolePermissions as $perm) {
        $allPermissions[$perm['permission_key']] = $perm;
    }

    // Override with user-specific permissions
    foreach ($userPermissions as $perm) {
        if ($perm['is_granted'] == 0) {
            unset($allPermissions[$perm['permission_key']]);
        } else {
            $allPermissions[$perm['permission_key']] = $perm;
        }
    }

    echo json_encode([
        'success' => true,
        'roles' => $roles,
        'permissions' => array_values($allPermissions),
        'overrides' => $userPermissions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
