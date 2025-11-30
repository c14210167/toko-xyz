<?php
/**
 * API: Add New Employee
 * Create new employee and assign role
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
if (!hasAnyPermission(['manage_roles', 'manage_permissions'])) {
    echo json_encode(['success' => false, 'error' => 'No permission']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'password', 'phone', 'role_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit();
        }
    }

    // Validate password length
    if (strlen($data['password']) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
        exit();
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit();
    }

    $conn->beginTransaction();

    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':email', $data['email']);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Email already exists');
    }

    // Get role name for user_type
    $role_query = "SELECT role_name FROM roles WHERE role_id = :role_id";
    $role_stmt = $conn->prepare($role_query);
    $role_stmt->bindParam(':role_id', $data['role_id']);
    $role_stmt->execute();
    $role = $role_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        throw new Exception('Invalid role');
    }

    // Security: Only owners can assign Owner role
    if ($role['role_name'] == 'Owner' && $_SESSION['user_type'] != 'owner') {
        throw new Exception('Only owners can assign the Owner role');
    }

    // Determine user_type based on role
    $user_type = strtolower($role['role_name']);
    if (!in_array($user_type, ['owner', 'staff', 'customer'])) {
        $user_type = 'staff';
    }

    // Insert user
    $insert_query = "INSERT INTO users (
                        first_name,
                        last_name,
                        email,
                        password,
                        phone,
                        address,
                        user_type,
                        created_at
                    ) VALUES (
                        :first_name,
                        :last_name,
                        :email,
                        :password,
                        :phone,
                        :address,
                        :user_type,
                        NOW()
                    )";

    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':first_name', $data['first_name']);
    $insert_stmt->bindParam(':last_name', $data['last_name']);
    $insert_stmt->bindParam(':email', $data['email']);

    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    $insert_stmt->bindParam(':password', $hashed_password);
    $insert_stmt->bindParam(':phone', $data['phone']);

    $address = $data['address'] ?? '';
    $insert_stmt->bindParam(':address', $address);
    $insert_stmt->bindParam(':user_type', $user_type);

    $insert_stmt->execute();
    $new_user_id = $conn->lastInsertId();

    // Assign role
    $role_insert = "INSERT INTO user_roles (user_id, role_id, assigned_at)
                    VALUES (:user_id, :role_id, NOW())";
    $role_stmt = $conn->prepare($role_insert);
    $role_stmt->bindParam(':user_id', $new_user_id);
    $role_stmt->bindParam(':role_id', $data['role_id']);
    $role_stmt->execute();

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('CREATE_EMPLOYEE', "Created new employee: {$data['first_name']} {$data['last_name']}");
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Employee added successfully',
        'employee_id' => $new_user_id
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Add Employee Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
