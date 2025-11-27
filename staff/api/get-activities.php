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
    $user_id = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? intval($_GET['user_id']) : null;
    $action_type = isset($_GET['action_type']) && $_GET['action_type'] !== '' ? $_GET['action_type'] : null;
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 50;
    $offset = ($page - 1) * $per_page;

    // Build WHERE clause
    $where_conditions = [];
    $params = [];

    if ($user_id) {
        $where_conditions[] = "al.user_id = :user_id";
        $params[':user_id'] = $user_id;
    }

    if ($action_type) {
        $where_conditions[] = "al.action_type = :action_type";
        $params[':action_type'] = $action_type;
    }

    // Date filter
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(al.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $where_conditions[] = "DATE(al.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where_conditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_query = "SELECT COUNT(*) as total
                    FROM activity_logs al
                    $where_clause";
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get activities with pagination
    $query = "SELECT
                al.*,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                u.email as user_email
              FROM activity_logs al
              JOIN users u ON al.user_id = u.user_id
              $where_clause
              ORDER BY al.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
