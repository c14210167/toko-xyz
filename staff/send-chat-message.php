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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$order_id = $data['order_id'] ?? null;
$receiver_id = $data['receiver_id'] ?? null; // Customer ID

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit();
}

if (!$receiver_id) {
    echo json_encode(['success' => false, 'message' => 'Receiver ID is required']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Insert message
    $query = "INSERT INTO messages (order_id, sender_id, receiver_id, message, is_read, created_at)
              VALUES (:order_id, :sender_id, :receiver_id, :message, 0, NOW())";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':sender_id', $_SESSION['user_id']);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message', $message);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message_id' => $conn->lastInsertId(),
            'message' => [
                'message' => $message,
                'created_at' => date('H:i'),
                'is_mine' => true
            ]
        ]);
    } else {
        throw new Exception('Failed to send message');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
