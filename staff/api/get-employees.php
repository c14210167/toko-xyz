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
    // Get filter parameters
    $role_filter = $_GET['role'] ?? 'all';
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Build query
    $query = "SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.user_type as old_role,
                u.created_at,
                GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
              FROM users u
              LEFT JOIN user_roles ur ON u.user_id = ur.user_id
              LEFT JOIN roles r ON ur.role_id = r.role_id
              WHERE u.user_type != 'customer'";

    // Add search filter
    if (!empty($search)) {
        $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
    }

    $query .= " GROUP BY u.user_id";

    // Add role filter (this needs to be after GROUP BY)
    if ($role_filter != 'all') {
        $query .= " HAVING FIND_IN_SET(:role_filter, roles) > 0";
    }

    $query .= " ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($query);

    if (!empty($search)) {
        $search_param = "%$search%";
        $stmt->bindParam(':search', $search_param);
    }

    if ($role_filter != 'all') {
        $stmt->bindParam(':role_filter', $role_filter);
    }

    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    $formatted_employees = array_map(function($emp) {
        return [
            'user_id' => $emp['user_id'],
            'name' => $emp['first_name'] . ' ' . $emp['last_name'],
            'email' => $emp['email'],
            'phone' => $emp['phone'] ?? 'N/A',
            'old_role' => $emp['old_role'],
            'roles' => $emp['roles'] ?? 'No roles assigned',
            'created_at' => $emp['created_at'],
            'avatar' => strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1))
        ];
    }, $employees);

    echo json_encode([
        'success' => true,
        'employees' => $formatted_employees
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
