<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        header('Location: register.php?error=password_mismatch');
        exit();
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Cek email sudah terdaftar
    $check_query = "SELECT user_id FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        header('Location: register.php?error=email_exists');
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user baru sebagai customer
    $query = "INSERT INTO users (email, password, first_name, last_name, phone, address, user_type) 
              VALUES (:email, :password, :first_name, :last_name, :phone, :address, 'customer')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    
    if ($stmt->execute()) {
        $user_id = $conn->lastInsertId();
        
        // Assign Customer role (role_id = 3)
        $role_query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, 3)";
        $role_stmt = $conn->prepare($role_query);
        $role_stmt->bindParam(':user_id', $user_id);
        $role_stmt->execute();
        
        // Auto login
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_address'] = $address;
        $_SESSION['user_type'] = 'customer';
        
        header('Location: index.php');
        exit();
    } else {
        header('Location: register.php?error=registration_failed');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>