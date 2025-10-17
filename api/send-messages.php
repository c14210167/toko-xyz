<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';
    $order_id = $data['order_id'] ?? null;
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit();
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Insert message (receiver_id NULL = ke customer support)
    $query = "INSERT INTO messages (order_id, sender_id, message) 
              VALUES (:order_id, :sender_id, :message)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':sender_id', $_SESSION['user_id']);
    $stmt->bindParam(':message', $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => [
                'message_id' => $conn->lastInsertId(),
                'message' => $message,
                'created_at' => date('H:i')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>