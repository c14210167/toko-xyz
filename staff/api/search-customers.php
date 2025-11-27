<?php
/**
 * API: Search Customers/Members
 * Search for existing customers to add to order
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('view_customers')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (empty($search)) {
        echo json_encode(['success' => false, 'message' => 'Search term required']);
        exit();
    }

    // Search customers by name, email, or phone
    $query = "SELECT
                user_id,
                CONCAT(first_name, ' ', last_name) as full_name,
                email,
                phone,
                address,
                created_at
              FROM users
              WHERE role = 'customer'
              AND (
                  CONCAT(first_name, ' ', last_name) LIKE :search1
                  OR email LIKE :search2
                  OR phone LIKE :search3
              )
              ORDER BY first_name
              LIMIT 20";

    $stmt = $conn->prepare($query);
    $search_param = "%{$search}%";
    $stmt->bindParam(':search1', $search_param);
    $stmt->bindParam(':search2', $search_param);
    $stmt->bindParam(':search3', $search_param);
    $stmt->execute();

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'customers' => $customers
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
