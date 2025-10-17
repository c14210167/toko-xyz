<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$order_number = $_GET['order_number'] ?? '';

if (empty($order_number)) {
    echo json_encode(['success' => false, 'message' => 'Order number required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get order details
$query = "SELECT o.*, 
          CONCAT(u.first_name, ' ', u.last_name) as customer_name,
          CONCAT(t.first_name, ' ', t.last_name) as technician_name,
          l.name as location_name
          FROM orders o
          JOIN users u ON o.user_id = u.user_id
          LEFT JOIN users t ON o.technician_id = t.user_id
          JOIN locations l ON o.location_id = l.location_id
          WHERE o.order_number = :order_number AND o.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':order_number', $order_number);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get timeline
$timeline_query = "SELECT * FROM order_timeline 
                  WHERE order_id = :order_id 
                  ORDER BY event_date ASC";
$timeline_stmt = $conn->prepare($timeline_query);
$timeline_stmt->bindParam(':order_id', $order['order_id']);
$timeline_stmt->execute();
$timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get costs
$cost_query = "SELECT * FROM order_costs WHERE order_id = :order_id";
$cost_stmt = $conn->prepare($cost_query);
$cost_stmt->bindParam(':order_id', $order['order_id']);
$cost_stmt->execute();
$costs = $cost_stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'order' => $order,
    'timeline' => $timeline,
    'costs' => $costs
]);
?>