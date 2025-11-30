<?php
/**
 * API: Create POS Transaction
 * Process a sale transaction through POS
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

    // Validate required fields
    if (empty($data['items']) || !is_array($data['items'])) {
        echo json_encode(['success' => false, 'error' => 'No items in transaction']);
        exit();
    }

    if (empty($data['payment_method'])) {
        echo json_encode(['success' => false, 'error' => 'Payment method is required']);
        exit();
    }

    $conn->beginTransaction();

    // Verify active session
    $session_query = "SELECT session_id FROM pos_sessions
                      WHERE user_id = :user_id AND status = 'open'
                      ORDER BY opened_at DESC LIMIT 1";
    $session_stmt = $conn->prepare($session_query);
    $session_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $session_stmt->execute();
    $session = $session_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new Exception('No active POS session found');
    }

    // Calculate totals
    $subtotal = 0;
    $items_validated = [];

    foreach ($data['items'] as $item) {
        // Verify product exists and has stock from inventory_items
        $product_query = "SELECT item_id, name,
                         COALESCE(item_code, CONCAT('SKU-', item_id)) as sku,
                         COALESCE(selling_price, ROUND(unit_price * 1.3, 2)) as selling_price,
                         quantity
                         FROM inventory_items WHERE item_id = :product_id";
        $product_stmt = $conn->prepare($product_query);
        $product_stmt->bindParam(':product_id', $item['product_id']);
        $product_stmt->execute();
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found: {$item['product_id']}");
        }

        if ($product['quantity'] < $item['quantity']) {
            throw new Exception("Insufficient stock for: {$product['name']}");
        }

        $item_subtotal = $product['selling_price'] * $item['quantity'];
        $discount_amount = isset($item['discount']) ? $item['discount'] : 0;
        $item_total = $item_subtotal - $discount_amount;

        $items_validated[] = [
            'product_id' => $product['item_id'],
            'product_name' => $product['name'],
            'sku' => $product['sku'],
            'quantity' => intval($item['quantity']),
            'unit_price' => floatval($product['selling_price']),
            'discount_amount' => floatval($discount_amount),
            'subtotal' => $item_total
        ];

        $subtotal += $item_total;
    }

    $discount_total = isset($data['discount']) ? floatval($data['discount']) : 0;
    $tax_amount = isset($data['tax']) ? floatval($data['tax']) : 0;
    $final_amount = $subtotal - $discount_total + $tax_amount;

    // Validate payment amounts
    $payment_method = $data['payment_method'];
    $cash_amount = 0;
    $card_amount = 0;
    $transfer_amount = 0;
    $change_amount = 0;

    switch ($payment_method) {
        case 'cash':
            $cash_amount = isset($data['cash_received']) ? floatval($data['cash_received']) : $final_amount;
            if ($cash_amount < $final_amount) {
                throw new Exception('Insufficient cash amount');
            }
            $change_amount = $cash_amount - $final_amount;
            $cash_amount = $final_amount; // Store only the actual amount (not including change)
            break;
        case 'card':
            $card_amount = $final_amount;
            break;
        case 'qris':
            $transfer_amount = $final_amount; // Using transfer_amount column for QRIS
            break;
        case 'split':
            $cash_amount = isset($data['cash_amount']) ? floatval($data['cash_amount']) : 0;
            $card_amount = isset($data['card_amount']) ? floatval($data['card_amount']) : 0;
            $transfer_amount = isset($data['transfer_amount']) ? floatval($data['transfer_amount']) : 0;

            if (($cash_amount + $card_amount + $transfer_amount) < $final_amount) {
                throw new Exception('Total payment amount is less than transaction amount');
            }
            break;
    }

    // Generate transaction number
    $transaction_number = 'POS-' . date('Ymd') . '-' . str_pad($session['session_id'], 4, '0', STR_PAD_LEFT) . '-' . time();

    // Insert transaction
    $trans_query = "INSERT INTO pos_transactions (
                        session_id,
                        transaction_number,
                        customer_id,
                        total_amount,
                        discount_amount,
                        tax_amount,
                        final_amount,
                        payment_method,
                        cash_amount,
                        card_amount,
                        transfer_amount,
                        change_amount,
                        notes,
                        created_by,
                        status
                    ) VALUES (
                        :session_id,
                        :transaction_number,
                        :customer_id,
                        :total_amount,
                        :discount_amount,
                        :tax_amount,
                        :final_amount,
                        :payment_method,
                        :cash_amount,
                        :card_amount,
                        :transfer_amount,
                        :change_amount,
                        :notes,
                        :created_by,
                        'completed'
                    )";

    $trans_stmt = $conn->prepare($trans_query);
    $trans_stmt->bindParam(':session_id', $session['session_id']);
    $trans_stmt->bindParam(':transaction_number', $transaction_number);

    $customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
    $trans_stmt->bindParam(':customer_id', $customer_id);

    $trans_stmt->bindParam(':total_amount', $subtotal);
    $trans_stmt->bindParam(':discount_amount', $discount_total);
    $trans_stmt->bindParam(':tax_amount', $tax_amount);
    $trans_stmt->bindParam(':final_amount', $final_amount);
    $trans_stmt->bindParam(':payment_method', $payment_method);
    $trans_stmt->bindParam(':cash_amount', $cash_amount);
    $trans_stmt->bindParam(':card_amount', $card_amount);
    $trans_stmt->bindParam(':transfer_amount', $transfer_amount);
    $trans_stmt->bindParam(':change_amount', $change_amount);

    $notes = $data['notes'] ?? '';
    $trans_stmt->bindParam(':notes', $notes);
    $trans_stmt->bindParam(':created_by', $_SESSION['user_id']);

    $trans_stmt->execute();
    $transaction_id = $conn->lastInsertId();

    // Insert transaction items and update stock
    foreach ($items_validated as $item) {
        // Insert item
        $item_query = "INSERT INTO pos_transaction_items (
                          transaction_id,
                          product_id,
                          product_name,
                          sku,
                          quantity,
                          unit_price,
                          discount_amount,
                          subtotal
                       ) VALUES (
                          :transaction_id,
                          :product_id,
                          :product_name,
                          :sku,
                          :quantity,
                          :unit_price,
                          :discount_amount,
                          :subtotal
                       )";

        $item_stmt = $conn->prepare($item_query);
        $item_stmt->bindParam(':transaction_id', $transaction_id);
        $item_stmt->bindParam(':product_id', $item['product_id']);
        $item_stmt->bindParam(':product_name', $item['product_name']);
        $item_stmt->bindParam(':sku', $item['sku']);
        $item_stmt->bindParam(':quantity', $item['quantity']);
        $item_stmt->bindParam(':unit_price', $item['unit_price']);
        $item_stmt->bindParam(':discount_amount', $item['discount_amount']);
        $item_stmt->bindParam(':subtotal', $item['subtotal']);
        $item_stmt->execute();

        // Update stock in inventory_items
        $stock_query = "UPDATE inventory_items
                       SET quantity = quantity - :quantity,
                           updated_at = CURRENT_TIMESTAMP
                       WHERE item_id = :product_id";
        $stock_stmt = $conn->prepare($stock_query);
        $stock_stmt->bindParam(':quantity', $item['quantity']);
        $stock_stmt->bindParam(':product_id', $item['product_id']);
        $stock_stmt->execute();
    }

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('POS_SALE', "Completed POS sale $transaction_number - Rp " . number_format($final_amount, 2));
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction completed successfully',
        'transaction' => [
            'transaction_id' => $transaction_id,
            'transaction_number' => $transaction_number,
            'final_amount' => $final_amount,
            'change_amount' => $change_amount
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Create POS Transaction Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
