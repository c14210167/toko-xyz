<?php
/**
 * API: Get Employee Information
 * Get employee data for editing
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasAnyPermission(['manage_roles', 'manage_permissions'])) {
    echo json_encode(['error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($employee_id <= 0) {
        echo json_encode(['error' => 'Invalid employee ID']);
        exit();
    }

    // Get employee data
    $query = "SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.address,
                u.user_type,
                u.created_at,
                ur.role_id
              FROM users u
              LEFT JOIN user_roles ur ON u.user_id = ur.user_id
              WHERE u.user_id = :user_id
              AND u.user_type != 'customer'";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->execute();

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['error' => 'Employee not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'current_user_type' => $_SESSION['user_type']
    ]);

} catch (PDOException $e) {
    error_log("Get Employee Info Error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
