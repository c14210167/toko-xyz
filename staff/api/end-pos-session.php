<?php
/**
 * API: End POS Session
 * Closes the current user's active POS session
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

    // Validate closing balance
    if (!isset($data['closing_balance'])) {
        echo json_encode(['success' => false, 'error' => 'Closing balance is required']);
        exit();
    }

    $closing_balance = floatval($data['closing_balance']);
    if ($closing_balance < 0) {
        echo json_encode(['success' => false, 'error' => 'Closing balance cannot be negative']);
        exit();
    }

    $conn->beginTransaction();

    // Get active session
    $session_query = "SELECT
                        s.*,
                        COALESCE(SUM(CASE WHEN t.payment_method = 'cash' THEN t.cash_amount ELSE 0 END), 0) as cash_sales,
                        COALESCE(SUM(CASE WHEN t.payment_method = 'card' THEN t.card_amount ELSE 0 END), 0) as card_sales,
                        COALESCE(SUM(CASE WHEN t.payment_method = 'qris' THEN t.transfer_amount ELSE 0 END), 0) as qris_sales,
                        COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.final_amount ELSE 0 END), 0) as total_sales,
                        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as transaction_count
                      FROM pos_sessions s
                      LEFT JOIN pos_transactions t ON s.session_id = t.session_id
                      WHERE s.user_id = :user_id AND s.status = 'open'
                      GROUP BY s.session_id";

    $session_stmt = $conn->prepare($session_query);
    $session_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $session_stmt->execute();
    $session = $session_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'No active session found']);
        exit();
    }

    // Calculate expected balance (opening + cash sales - cash movements)
    $cash_movements_query = "SELECT
                                COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in,
                                COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out
                              FROM pos_cash_movements
                              WHERE session_id = :session_id";

    $movements_stmt = $conn->prepare($cash_movements_query);
    $movements_stmt->bindParam(':session_id', $session['session_id']);
    $movements_stmt->execute();
    $movements = $movements_stmt->fetch(PDO::FETCH_ASSOC);

    $expected_balance = $session['opening_balance']
                      + $session['cash_sales']
                      + $movements['cash_in']
                      - $movements['cash_out'];

    // Update session
    $update_query = "UPDATE pos_sessions SET
                        closed_at = NOW(),
                        closing_balance = :closing_balance,
                        expected_balance = :expected_balance,
                        cash_sales = :cash_sales,
                        card_sales = :card_sales,
                        qris_sales = :qris_sales,
                        total_sales = :total_sales,
                        total_transactions = :total_transactions,
                        closing_notes = :closing_notes,
                        status = 'closed'
                     WHERE session_id = :session_id";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':closing_balance', $closing_balance);
    $update_stmt->bindParam(':expected_balance', $expected_balance);
    $update_stmt->bindParam(':cash_sales', $session['cash_sales']);
    $update_stmt->bindParam(':card_sales', $session['card_sales']);
    $update_stmt->bindParam(':qris_sales', $session['qris_sales']);
    $update_stmt->bindParam(':total_sales', $session['total_sales']);
    $update_stmt->bindParam(':total_transactions', $session['transaction_count']);

    $closing_notes = $data['notes'] ?? '';
    $update_stmt->bindParam(':closing_notes', $closing_notes);
    $update_stmt->bindParam(':session_id', $session['session_id']);

    $update_stmt->execute();

    // Calculate variance
    $variance = $closing_balance - $expected_balance;

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $variance_text = $variance >= 0 ? "+Rp " . number_format($variance, 2) : "-Rp " . number_format(abs($variance), 2);
        $logger->log('END_POS_SESSION', "Ended POS session #{$session['session_id']}. Transactions: {$session['transaction_count']}, Variance: $variance_text");
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'POS session ended successfully',
        'session_summary' => [
            'session_id' => $session['session_id'],
            'opening_balance' => $session['opening_balance'],
            'closing_balance' => $closing_balance,
            'expected_balance' => $expected_balance,
            'variance' => $variance,
            'total_transactions' => $session['transaction_count'],
            'cash_sales' => $session['cash_sales'],
            'card_sales' => $session['card_sales'],
            'qris_sales' => $session['qris_sales'],
            'total_sales' => $session['total_sales']
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("End POS Session Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
