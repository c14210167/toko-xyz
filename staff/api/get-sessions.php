<?php
/**
 * API: Get POS Sessions
 * Get all POS sessions with pagination and filters
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasAnyPermission(['access_pos', 'create_transaction', 'view_all_orders'])) {
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get filters
    $status = $_GET['status'] ?? 'all';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    // Build query
    $query = "SELECT
                s.session_id,
                s.user_id,
                s.location_id,
                s.opened_at,
                s.closed_at,
                s.opening_balance,
                s.closing_balance,
                s.expected_balance,
                s.cash_sales,
                s.card_sales,
                s.qris_sales,
                s.total_sales,
                s.total_transactions,
                s.opening_notes,
                s.closing_notes,
                s.status,
                CONCAT(u.first_name, ' ', u.last_name) as cashier_name,
                l.name as location_name,
                (s.closing_balance - s.expected_balance) as variance
              FROM pos_sessions s
              LEFT JOIN users u ON s.user_id = u.user_id
              LEFT JOIN locations l ON s.location_id = l.location_id
              WHERE 1=1";

    $params = [];

    // Status filter
    if ($status !== 'all') {
        $query .= " AND s.status = :status";
        $params[':status'] = $status;
    }

    // Count total
    $count_query = preg_replace('/^SELECT.*FROM/s', 'SELECT COUNT(*) as total FROM', $query);
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add sorting and pagination
    $query .= " ORDER BY s.opened_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $sessions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sessions[] = [
            'session_id' => $row['session_id'],
            'cashier_name' => $row['cashier_name'],
            'location_name' => $row['location_name'] ?? 'N/A',
            'opened_at' => $row['opened_at'],
            'closed_at' => $row['closed_at'],
            'opening_balance' => (float)$row['opening_balance'],
            'closing_balance' => (float)$row['closing_balance'],
            'expected_balance' => (float)$row['expected_balance'],
            'variance' => (float)$row['variance'],
            'cash_sales' => (float)$row['cash_sales'],
            'card_sales' => (float)$row['card_sales'],
            'qris_sales' => (float)$row['qris_sales'],
            'total_sales' => (float)$row['total_sales'],
            'total_transactions' => (int)$row['total_transactions'],
            'opening_notes' => $row['opening_notes'] ?? '',
            'closing_notes' => $row['closing_notes'] ?? '',
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'sessions' => $sessions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => (int)$total,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log("Get Sessions Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
