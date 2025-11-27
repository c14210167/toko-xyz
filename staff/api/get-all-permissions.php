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

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get all permissions grouped by category
    $query = "SELECT permission_id, permission_key, permission_name, description, category
              FROM permissions
              ORDER BY category, permission_name";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $permissions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $row['category'] ?? 'other';
        if (!isset($permissions[$category])) {
            $permissions[$category] = [];
        }
        $permissions[$category][] = $row;
    }

    echo json_encode([
        'success' => true,
        'permissions' => $permissions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
