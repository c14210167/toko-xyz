<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if logged in and staff/owner
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SESSION['user_type'] == 'customer') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$customer_id = $_GET['id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Customer ID required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get customer info
$customer_query = "SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.email,
    u.phone,
    u.address,
    u.created_at,
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oc.total_cost ELSE 0 END), 0) as total_revenue
FROM users u
LEFT JOIN orders o ON u.user_id = o.user_id
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
WHERE u.user_id = :customer_id AND u.user_type = 'customer'
GROUP BY u.user_id";

$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bindParam(':customer_id', $customer_id);
$customer_stmt->execute();
$customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
    exit();
}

// Get orders (newest first)
$orders_query = "SELECT 
    o.order_id,
    o.order_number,
    o.device_type,
    o.brand,
    o.model,
    o.issue_type,
    o.status,
    o.created_at,
    l.name as location_name,
    COALESCE(oc.total_cost, 0) as total_cost
FROM orders o
LEFT JOIN locations l ON o.location_id = l.location_id
LEFT JOIN order_costs oc ON o.order_id = oc.order_id
WHERE o.user_id = :customer_id
ORDER BY o.created_at DESC";

$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bindParam(':customer_id', $customer_id);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'customer' => $customer,
    'orders' => $orders
]);
?>