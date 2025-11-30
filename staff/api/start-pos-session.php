<?php
/**
 * API: Start POS Session
 * Opens a new cashier session for the current user
 */

session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';
require_once '../../includes/ActivityLogger.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasAnyPermission(['access_pos', 'create_transaction'])) {
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    // Validate opening balance
    $opening_balance = isset($data['opening_balance']) ? floatval($data['opening_balance']) : 0;
    if ($opening_balance < 0) {
        echo json_encode(['success' => false, 'error' => 'Opening balance cannot be negative']);
        exit();
    }

    // Check if user already has an open session
    $check_query = "SELECT session_id FROM pos_sessions
                    WHERE user_id = :user_id AND status = 'open'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'You already have an open session']);
        exit();
    }

    $conn->beginTransaction();

    // Get user's location (if available)
    $location_id = isset($_SESSION['location_id']) ? $_SESSION['location_id'] : null;

    // Create new session
    $insert_query = "INSERT INTO pos_sessions (
                        user_id,
                        location_id,
                        opened_at,
                        opening_balance,
                        opening_notes,
                        status
                    ) VALUES (
                        :user_id,
                        :location_id,
                        NOW(),
                        :opening_balance,
                        :opening_notes,
                        'open'
                    )";

    $stmt = $conn->prepare($insert_query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':location_id', $location_id);
    $stmt->bindParam(':opening_balance', $opening_balance);

    $opening_notes = $data['notes'] ?? '';
    $stmt->bindParam(':opening_notes', $opening_notes);

    $stmt->execute();
    $session_id = $conn->lastInsertId();

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('START_POS_SESSION', "Started POS session #$session_id with opening balance: Rp " . number_format($opening_balance, 2));
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'POS session started successfully',
        'session_id' => $session_id
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Start POS Session Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
