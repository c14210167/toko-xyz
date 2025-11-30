<?php
/**
 * API: Get POS Session Summary
 * Returns current session statistics
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
if (!hasAnyPermission(['access_pos', 'create_transaction', 'view_all_orders'])) {
    echo json_encode(['error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get active session with summary
    $query = "SELECT
                s.*,
                COALESCE(SUM(CASE WHEN t.payment_method = 'cash' OR t.cash_amount > 0 THEN t.cash_amount ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN t.payment_method = 'card' OR t.card_amount > 0 THEN t.card_amount ELSE 0 END), 0) as card_sales,
                COALESCE(SUM(CASE WHEN t.payment_method = 'qris' OR t.transfer_amount > 0 THEN t.transfer_amount ELSE 0 END), 0) as qris_sales,
                COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.final_amount ELSE 0 END), 0) as total_sales,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as transaction_count
              FROM pos_sessions s
              LEFT JOIN pos_transactions t ON s.session_id = t.session_id
              WHERE s.user_id = :user_id AND s.status = 'open'
              GROUP BY s.session_id";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['error' => 'No active session found']);
        exit();
    }

    // Get cash movements
    $movements_query = "SELECT
                          COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in,
                          COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out
                        FROM pos_cash_movements
                        WHERE session_id = :session_id";

    $movements_stmt = $conn->prepare($movements_query);
    $movements_stmt->bindParam(':session_id', $session['session_id']);
    $movements_stmt->execute();
    $movements = $movements_stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate expected balance
    $expected_balance = $session['opening_balance']
                      + $session['cash_sales']
                      + $movements['cash_in']
                      - $movements['cash_out'];

    echo json_encode([
        'success' => true,
        'session' => [
            'session_id' => $session['session_id'],
            'opened_at' => $session['opened_at'],
            'opening_balance' => floatval($session['opening_balance']),
            'cash_sales' => floatval($session['cash_sales']),
            'card_sales' => floatval($session['card_sales']),
            'qris_sales' => floatval($session['qris_sales']),
            'total_sales' => floatval($session['total_sales']),
            'transaction_count' => intval($session['transaction_count']),
            'cash_in' => floatval($movements['cash_in']),
            'cash_out' => floatval($movements['cash_out']),
            'expected_balance' => $expected_balance
        ]
    ]);

} catch (PDOException $e) {
    error_log("Get Session Summary Error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
