<?php
/**
 * API: Get Customers for POS
 * Returns customer list for POS customer selection
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
if (!hasAnyPermission(['access_pos', 'create_transaction'])) {
    echo json_encode(['error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get customers only
    $query = "SELECT
                user_id,
                CONCAT(first_name, ' ', last_name) as customer_name,
                email,
                phone
              FROM users
              WHERE user_type = 'customer'
              ORDER BY first_name, last_name";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'customers' => $customers
    ]);

} catch (PDOException $e) {
    error_log("Get POS Customers Error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
