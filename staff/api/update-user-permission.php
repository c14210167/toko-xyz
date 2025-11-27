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

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;
$permissionId = $input['permission_id'] ?? null;
$isGranted = $input['is_granted'] ?? true;

if (!$userId || !$permissionId) {
    echo json_encode(['success' => false, 'message' => 'User ID and Permission ID required']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Update or insert user permission
    $query = "INSERT INTO user_permissions (user_id, permission_id, is_granted, granted_by)
              VALUES (:user_id, :permission_id, :is_granted, :granted_by)
              ON DUPLICATE KEY UPDATE is_granted = :is_granted, granted_by = :granted_by";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':permission_id', $permissionId);
    $stmt->bindParam(':is_granted', $isGranted, PDO::PARAM_INT);
    $stmt->bindParam(':granted_by', $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Permission updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update permission'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
