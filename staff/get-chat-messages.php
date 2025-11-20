<?php
session_start();
require_once '../config/database.php';

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

$order_id = $_GET['order_id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

if (!$order_id && !$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID or Customer ID is required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Build query based on filter
if ($order_id) {
    // Get messages for specific order
    $query = "SELECT
        m.message_id,
        m.message,
        m.sender_id,
        m.receiver_id,
        m.created_at,
        m.is_read,
        CONCAT(sender.first_name, ' ', sender.last_name) as sender_name,
        sender.role as sender_role,
        CONCAT(receiver.first_name, ' ', receiver.last_name) as receiver_name
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
    WHERE m.order_id = :order_id
    ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
} else {
    // Get all messages with specific customer (across all orders)
    $query = "SELECT
        m.message_id,
        m.message,
        m.sender_id,
        m.receiver_id,
        m.order_id,
        m.created_at,
        m.is_read,
        CONCAT(sender.first_name, ' ', sender.last_name) as sender_name,
        sender.role as sender_role,
        CONCAT(receiver.first_name, ' ', receiver.last_name) as receiver_name,
        o.order_number
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    LEFT JOIN users receiver ON m.receiver_id = receiver.user_id
    LEFT JOIN orders o ON m.order_id = o.order_id
    WHERE (m.sender_id = :customer_id OR m.receiver_id = :customer_id)
    ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id);
}

$stmt->execute();
$messages = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $is_mine = ($row['sender_id'] == $_SESSION['user_id']);

    $messages[] = [
        'message_id' => $row['message_id'],
        'message' => $row['message'],
        'sender_id' => $row['sender_id'],
        'sender_name' => $row['sender_name'],
        'sender_role' => $row['sender_role'],
        'receiver_id' => $row['receiver_id'],
        'receiver_name' => $row['receiver_name'],
        'is_mine' => $is_mine,
        'created_at' => date('d M Y H:i', strtotime($row['created_at'])),
        'time_only' => date('H:i', strtotime($row['created_at'])),
        'is_read' => (bool)$row['is_read'],
        'order_id' => $row['order_id'] ?? null,
        'order_number' => $row['order_number'] ?? null
    ];
}

// Mark messages as read (messages sent by customer to staff)
if ($order_id) {
    $mark_read_query = "UPDATE messages SET is_read = 1
                        WHERE order_id = :order_id
                        AND receiver_id = :staff_id";
    $mark_stmt = $conn->prepare($mark_read_query);
    $mark_stmt->bindParam(':order_id', $order_id);
    $mark_stmt->bindParam(':staff_id', $_SESSION['user_id']);
    $mark_stmt->execute();
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>
