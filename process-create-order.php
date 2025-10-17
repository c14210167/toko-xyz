<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log untuk debugging
file_put_contents('debug_order.txt', "=== NEW REQUEST ===\n", FILE_APPEND);
file_put_contents('debug_order.txt', date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('debug_order.txt', "Session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents('debug_order.txt', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    file_put_contents('debug_order.txt', "ERROR: Not logged in\n", FILE_APPEND);
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    file_put_contents('debug_order.txt', "POST request received\n", FILE_APPEND);
    
    // Get form data
    $device_type = $_POST['device_type'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $model = $_POST['model'] ?? '';
    $serial_number = $_POST['serial_number'] ?? '';
    $additional_notes = $_POST['additional_notes'] ?? '';
    $warranty_status = isset($_POST['warranty_check']) ? 1 : 0;
    
    file_put_contents('debug_order.txt', "Device: $device_type, Issue: $issue_type, Brand: $brand, Model: $model\n", FILE_APPEND);
    
    if (empty($device_type) || empty($issue_type) || empty($brand) || empty($model)) {
        file_put_contents('debug_order.txt', "ERROR: Missing required fields\n", FILE_APPEND);
        header('Location: create-order.php?error=missing_fields');
        exit();
    }
    
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        file_put_contents('debug_order.txt', "ERROR: Database connection failed\n", FILE_APPEND);
        die('Database connection failed');
    }
    
    file_put_contents('debug_order.txt', "Database connected\n", FILE_APPEND);
    
    // Generate order number
    $year = date('Y');
    $check_query = "SELECT COUNT(*) as total FROM orders WHERE YEAR(created_at) = :year";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':year', $year);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $order_count = $result['total'] + 1;
    $order_number = 'SRV-' . str_pad($order_count, 6, '0', STR_PAD_LEFT);
    
    file_put_contents('debug_order.txt', "Order number generated: $order_number\n", FILE_APPEND);
    
    // Default location
    $location_id = 1;
    
    // Insert order
    try {
        $query = "INSERT INTO orders (order_number, user_id, location_id, device_type, brand, model, serial_number, issue_type, additional_notes, warranty_status, status) 
                  VALUES (:order_number, :user_id, :location_id, :device_type, :brand, :model, :serial_number, :issue_type, :additional_notes, :warranty_status, 'pending')";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_number', $order_number);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':device_type', $device_type);
        $stmt->bindParam(':brand', $brand);
        $stmt->bindParam(':model', $model);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->bindParam(':issue_type', $issue_type);
        $stmt->bindParam(':additional_notes', $additional_notes);
        $stmt->bindParam(':warranty_status', $warranty_status);
        
        file_put_contents('debug_order.txt', "Executing INSERT query...\n", FILE_APPEND);
        
        if ($stmt->execute()) {
            $order_id = $conn->lastInsertId();
            file_put_contents('debug_order.txt', "Order inserted successfully! Order ID: $order_id\n", FILE_APPEND);
            
            // Insert initial timeline
            $timeline_query = "INSERT INTO order_timeline (order_id, event_name, event_date, status) 
                              VALUES (:order_id, 'Order diterima', NOW(), 'completed')";
            $timeline_stmt = $conn->prepare($timeline_query);
            $timeline_stmt->bindParam(':order_id', $order_id);
            $timeline_stmt->execute();
            
            file_put_contents('debug_order.txt', "Timeline inserted\n", FILE_APPEND);
            
            // Insert costs
            $cost_query = "INSERT INTO order_costs (order_id, spareparts_cost, service_cost, other_cost) 
                          VALUES (:order_id, 0, 0, 0)";
            $cost_stmt = $conn->prepare($cost_query);
            $cost_stmt->bindParam(':order_id', $order_id);
            $cost_stmt->execute();
            
            file_put_contents('debug_order.txt', "Costs inserted\n", FILE_APPEND);
            file_put_contents('debug_order.txt', "SUCCESS! Redirecting...\n\n", FILE_APPEND);
            
            // Success
            header('Location: order-history.php?success=order_created&order_number=' . $order_number);
            exit();
        } else {
            $errorInfo = $stmt->errorInfo();
            file_put_contents('debug_order.txt', "ERROR executing query: " . print_r($errorInfo, true) . "\n", FILE_APPEND);
            header('Location: create-order.php?error=failed_to_create');
            exit();
        }
    } catch (Exception $e) {
        file_put_contents('debug_order.txt', "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        die('Error: ' . $e->getMessage());
    }
} else {
    file_put_contents('debug_order.txt', "ERROR: Not POST request\n", FILE_APPEND);
    header('Location: create-order.php');
    exit();
}
?>