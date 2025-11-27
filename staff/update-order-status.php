<?php
session_start();
require_once '../config/database.php';
require_once '../config/init_permissions.php';

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
$order_id = $data['order_id'] ?? null;
$new_status = $data['status'] ?? null;
$notes = $data['notes'] ?? '';

if (!$order_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit();
}

// Validate status
$valid_statuses = ['pending', 'in_progress', 'waiting_parts', 'completed', 'cancelled', 'ready_pickup'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    // Get current order info
    $query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email as customer_email
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              WHERE o.order_id = :order_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    $old_status = $order['status'];

    // Update order status
    $update_query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE order_id = :order_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':order_id', $order_id);
    $update_stmt->execute();

    // Log status change
    $log_query = "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes, created_at)
                  VALUES (:order_id, :old_status, :new_status, :changed_by, :notes, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bindParam(':order_id', $order_id);
    $log_stmt->bindParam(':old_status', $old_status);
    $log_stmt->bindParam(':new_status', $new_status);
    $log_stmt->bindParam(':changed_by', $_SESSION['user_id']);
    $log_stmt->bindParam(':notes', $notes);
    $log_stmt->execute();

    // Send notification message to customer
    $notification_message = generateStatusChangeMessage($old_status, $new_status, $order['order_number']);
    if ($notification_message) {
        $msg_query = "INSERT INTO messages (order_id, sender_id, receiver_id, message, created_at)
                      VALUES (:order_id, :sender_id, :receiver_id, :message, NOW())";
        $msg_stmt = $conn->prepare($msg_query);
        $msg_stmt->bindParam(':order_id', $order_id);
        $msg_stmt->bindParam(':sender_id', $_SESSION['user_id']);
        $msg_stmt->bindParam(':receiver_id', $order['user_id']);
        $msg_stmt->bindParam(':message', $notification_message);
        $msg_stmt->execute();
    }

    // Log activity
    require_once '../includes/ActivityLogger.php';
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->logOrderUpdate($order_id, $old_status, $new_status);
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'new_status' => $new_status,
        'order_number' => $order['order_number']
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateStatusChangeMessage($old_status, $new_status, $order_number) {
    $messages = [
        'pending' => "Order {$order_number} sedang menunggu pemrosesan.",
        'in_progress' => "Kabar baik! Order {$order_number} Anda sedang dikerjakan oleh teknisi kami.",
        'waiting_parts' => "Order {$order_number} sedang menunggu spare parts. Kami akan segera melanjutkan setelah parts tersedia.",
        'ready_pickup' => "Order {$order_number} sudah selesai dan siap diambil! Silakan kunjungi cabang kami.",
        'completed' => "Order {$order_number} telah selesai. Terima kasih atas kepercayaan Anda!",
        'cancelled' => "Order {$order_number} telah dibatalkan."
    ];

    return $messages[$new_status] ?? null;
}
?>
