<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get messages untuk user ini (baik sebagai sender atau receiver)
$query = "SELECT m.*, 
          CONCAT(u.first_name, ' ', u.last_name) as sender_name,
          u.role as sender_role
          FROM messages m
          JOIN users u ON m.sender_id = u.user_id
          WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
          ORDER BY m.created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        'message_id' => $row['message_id'],
        'message' => $row['message'],
        'sender_id' => $row['sender_id'],
        'sender_name' => $row['sender_name'],
        'sender_role' => $row['sender_role'],
        'is_mine' => ($row['sender_id'] == $_SESSION['user_id']),
        'created_at' => date('H:i', strtotime($row['created_at']))
    ];
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>