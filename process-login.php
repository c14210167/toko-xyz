<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT user_id, email, password, first_name, last_name, phone, address, user_type, location_id 
              FROM users WHERE email = :email AND is_active = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_address'] = $user['address'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_location_id'] = $user['location_id'];

            // Log login activity (only for staff/owner, not customers)
            if ($user['user_type'] != 'customer') {
                require_once 'includes/ActivityLogger.php';
                try {
                    $logger = new ActivityLogger($conn, $user['user_id']);
                    $logger->logLogin();
                } catch (Exception $e) {
                    error_log("Login logging error: " . $e->getMessage());
                }
            }

            // Redirect berdasarkan user type
            if ($user['user_type'] == 'customer') {
                header('Location: index.php');
            } else {
                // Staff atau owner ke dashboard pegawai
                header('Location: staff/dashboard.php');
            }
            exit();
        } else {
            header('Location: login.php?error=invalid_password');
            exit();
        }
    } else {
        header('Location: login.php?error=user_not_found');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>