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

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['user_id'] ?? null;
$roleId = $input['role_id'] ?? null;

if (!$userId || !$roleId) {
    echo json_encode(['success' => false, 'message' => 'User ID and Role ID required']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Remove role from user
    $query = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':role_id', $roleId);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Role removed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove role'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
