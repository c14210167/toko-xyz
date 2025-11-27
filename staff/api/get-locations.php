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
    // Get all locations with order count
    $query = "SELECT
                l.location_id,
                l.name,
                l.address,
                l.phone,
                l.created_at,
                COUNT(DISTINCT o.order_id) as order_count
              FROM locations l
              LEFT JOIN orders o ON l.location_id = o.location_id
              GROUP BY l.location_id
              ORDER BY l.name";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'locations' => $locations
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
