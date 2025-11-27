<?php
session_start();
require_once '../config/database.php';
require_once '../config/init_permissions.php';

// Clean output buffer to prevent any unwanted output
if (ob_get_level()) ob_clean();
header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if staff or owner
if ($_SESSION['user_type'] == 'customer') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$location = $_GET['location'] ?? 'all';
$sortBy = $_GET['sortBy'] ?? 'created_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Check if is_read column exists in messages table
$check_column = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
$has_is_read = $check_column->rowCount() > 0;

// Build query - conditionally include unread_messages based on column existence
$unread_subquery = $has_is_read
    ? "(SELECT COUNT(*) FROM messages m WHERE m.order_id = o.order_id AND m.sender_id = u.user_id AND m.is_read = 0)"
    : "0";

// Check which columns exist in orders table
$orders_columns_stmt = $conn->query("SHOW COLUMNS FROM orders");
$orders_columns = [];
while ($col = $orders_columns_stmt->fetch(PDO::FETCH_ASSOC)) {
    $orders_columns[] = $col['Field'];
}

// Build flexible query based on available columns
$service_type_col = in_array('service_type', $orders_columns) ? 'o.service_type' : "'' as service_type";
$device_info_col = in_array('device_info', $orders_columns) ? 'o.device_info' : "'' as device_info";
$issue_description_col = in_array('issue_description', $orders_columns) ? 'o.issue_description' :
                          (in_array('description', $orders_columns) ? 'o.description as issue_description' : "'' as issue_description");

$query = "SELECT
    o.order_id,
    o.order_number,
    $service_type_col,
    $device_info_col,
    $issue_description_col,
    o.status,
    o.created_at,
    o.updated_at,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
    u.phone as customer_phone,
    u.email as customer_email,
    u.user_id as customer_id,
    l.name as location_name,
    l.location_id,
    oc.total_cost,
    $unread_subquery as unread_messages
FROM orders o
JOIN users u ON o.user_id = u.user_id
LEFT JOIN locations l ON o.location_id = l.location_id
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
WHERE 1=1";

$params = [];

// Search filter - only search on existing columns
if (!empty($search)) {
    $search_conditions = [
        "o.order_number LIKE :search",
        "CONCAT(u.first_name, ' ', u.last_name) LIKE :search",
        "u.phone LIKE :search",
        "u.email LIKE :search"
    ];

    // Add device_info to search if column exists
    if (in_array('device_info', $orders_columns)) {
        $search_conditions[] = "o.device_info LIKE :search";
    }

    $query .= " AND (" . implode(" OR ", $search_conditions) . ")";
    $params[':search'] = '%' . $search . '%';
}

// Status filter
if ($status !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $status;
}

// Location filter
if ($location !== 'all') {
    $query .= " AND o.location_id = :location";
    $params[':location'] = $location;
}

// Sorting
switch ($sortBy) {
    case 'created_asc':
        $query .= " ORDER BY o.created_at ASC";
        break;
    case 'updated_desc':
        $query .= " ORDER BY o.updated_at DESC";
        break;
    case 'priority':
        $query .= " ORDER BY
            CASE o.status
                WHEN 'waiting_parts' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'pending' THEN 3
                WHEN 'completed' THEN 4
                ELSE 5
            END,
            o.created_at ASC";
        break;
    default: // created_desc
        $query .= " ORDER BY o.created_at DESC";
}

try {
    // Count total
    $count_query = preg_replace('/^SELECT.*FROM/s', 'SELECT COUNT(*) as total FROM', $query);
    $count_query = preg_replace('/ORDER BY.*$/s', '', $count_query);
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);

    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get status badge info
        $status_info = getStatusInfo($row['status']);

        $orders[] = [
            'order_id' => $row['order_id'],
            'order_number' => $row['order_number'],
            'customer_name' => $row['customer_name'],
            'customer_phone' => $row['customer_phone'],
            'customer_email' => $row['customer_email'],
            'customer_id' => $row['customer_id'],
            'service_type' => $row['service_type'],
            'device_info' => $row['device_info'],
            'issue_description' => $row['issue_description'],
            'status' => $row['status'],
            'status_label' => $status_info['label'],
            'status_class' => $status_info['class'],
            'location_name' => $row['location_name'],
            'location_id' => $row['location_id'],
            'total_cost' => $row['total_cost'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'unread_messages' => (int)$row['unread_messages']
        ];
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'query' => $query // For debugging
    ]);
}

function getStatusInfo($status) {
    $statuses = [
        'pending' => ['label' => 'Pending', 'class' => 'status-pending'],
        'in_progress' => ['label' => 'In Progress', 'class' => 'status-progress'],
        'waiting_parts' => ['label' => 'Waiting Parts', 'class' => 'status-waiting'],
        'completed' => ['label' => 'Completed', 'class' => 'status-completed'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled'],
        'ready_pickup' => ['label' => 'Ready Pickup', 'class' => 'status-ready']
    ];

    return $statuses[$status] ?? ['label' => ucfirst($status), 'class' => 'status-default'];
}
?>
