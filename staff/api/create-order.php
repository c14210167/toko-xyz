<?php
/**
 * API: Create New Order
 * Create order with member or guest customer
 */

header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check permission
if (!hasPermission('create_orders')) {
    echo json_encode(['success' => false, 'message' => 'No permission to create orders']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['service_type', 'device_type', 'problem_description', 'location_id'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            exit();
        }
    }

    $is_member = isset($data['is_member']) && $data['is_member'];
    $user_id = null;

    $conn->beginTransaction();

    // Handle member or guest
    if ($is_member) {
        // Use existing customer
        if (!isset($data['customer_id']) || empty($data['customer_id'])) {
            throw new Exception('Customer ID required for member');
        }
        $user_id = $data['customer_id'];
    } else {
        // Create guest customer account
        if (!isset($data['guest_name']) || !isset($data['guest_phone'])) {
            throw new Exception('Guest name and phone required');
        }

        // Check if guest email/phone already exists
        $check_query = "SELECT user_id FROM users WHERE email = :email OR phone = :phone LIMIT 1";
        $check_stmt = $conn->prepare($check_query);
        $guest_email = $data['guest_email'] ?? '';
        $guest_phone = $data['guest_phone'];
        $check_stmt->bindParam(':email', $guest_email);
        $check_stmt->bindParam(':phone', $guest_phone);
        $check_stmt->execute();
        $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            // Use existing user
            $user_id = $existing_user['user_id'];
        } else {
            // Create new guest customer
            $guest_name_parts = explode(' ', $data['guest_name'], 2);
            $first_name = $guest_name_parts[0];
            $last_name = isset($guest_name_parts[1]) ? $guest_name_parts[1] : '';

            $insert_user = "INSERT INTO users (first_name, last_name, email, phone, role, password)
                           VALUES (:first_name, :last_name, :email, :phone, 'customer', :password)";
            $user_stmt = $conn->prepare($insert_user);
            $user_stmt->bindParam(':first_name', $first_name);
            $user_stmt->bindParam(':last_name', $last_name);
            $user_stmt->bindParam(':email', $guest_email);
            $user_stmt->bindParam(':phone', $guest_phone);
            $default_password = password_hash('guest123', PASSWORD_DEFAULT);
            $user_stmt->bindParam(':password', $default_password);
            $user_stmt->execute();

            $user_id = $conn->lastInsertId();
        }
    }

    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Create order
    $create_order = "INSERT INTO orders (
                        order_number,
                        user_id,
                        service_type,
                        device_type,
                        brand,
                        model,
                        serial_number,
                        problem_description,
                        location_id,
                        status,
                        priority,
                        created_by
                    ) VALUES (
                        :order_number,
                        :user_id,
                        :service_type,
                        :device_type,
                        :brand,
                        :model,
                        :serial_number,
                        :problem_description,
                        :location_id,
                        'pending',
                        :priority,
                        :created_by
                    )";

    $order_stmt = $conn->prepare($create_order);
    $order_stmt->bindParam(':order_number', $order_number);
    $order_stmt->bindParam(':user_id', $user_id);
    $order_stmt->bindParam(':service_type', $data['service_type']);
    $order_stmt->bindParam(':device_type', $data['device_type']);
    $brand = $data['brand'] ?? '';
    $model = $data['model'] ?? '';
    $serial_number = $data['serial_number'] ?? '';
    $priority = $data['priority'] ?? 'normal';
    $order_stmt->bindParam(':brand', $brand);
    $order_stmt->bindParam(':model', $model);
    $order_stmt->bindParam(':serial_number', $serial_number);
    $order_stmt->bindParam(':problem_description', $data['problem_description']);
    $order_stmt->bindParam(':location_id', $data['location_id']);
    $order_stmt->bindParam(':priority', $priority);
    $order_stmt->bindParam(':created_by', $_SESSION['user_id']);
    $order_stmt->execute();

    $order_id = $conn->lastInsertId();

    // Create initial order cost record
    $create_cost = "INSERT INTO order_costs (order_id, service_cost, total_cost)
                   VALUES (:order_id, 0, 0)";
    $cost_stmt = $conn->prepare($create_cost);
    $cost_stmt->bindParam(':order_id', $order_id);
    $cost_stmt->execute();

    // Add initial timeline entry
    $timeline_entry = "INSERT INTO order_timeline (
                          order_id,
                          status,
                          notes,
                          updated_by
                      ) VALUES (
                          :order_id,
                          'pending',
                          'Order created',
                          :updated_by
                      )";
    $timeline_stmt = $conn->prepare($timeline_entry);
    $timeline_stmt->bindParam(':order_id', $order_id);
    $timeline_stmt->bindParam(':updated_by', $_SESSION['user_id']);
    $timeline_stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
