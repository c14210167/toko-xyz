<?php
/**
 * API: Update Employee
 * Update employee information and role
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
    if (empty($data['employee_id']) || empty($data['first_name']) || empty($data['last_name']) ||
        empty($data['email']) || empty($data['phone']) || empty($data['role_id'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }

    $employee_id = intval($data['employee_id']);

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit();
    }

    $conn->beginTransaction();

    // Check if employee exists
    $check_query = "SELECT user_id, user_type FROM users WHERE user_id = :user_id AND user_type != 'customer'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':user_id', $employee_id);
    $check_stmt->execute();
    $existing_employee = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing_employee) {
        throw new Exception('Employee not found');
    }

    // Security: Only owners can edit other owners
    if ($existing_employee['user_type'] == 'owner' && $_SESSION['user_type'] != 'owner') {
        throw new Exception('Only owners can edit other owner accounts');
    }

    // Check if email is taken by another user
    $email_check = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
    $email_stmt = $conn->prepare($email_check);
    $email_stmt->bindParam(':email', $data['email']);
    $email_stmt->bindParam(':user_id', $employee_id);
    $email_stmt->execute();

    if ($email_stmt->rowCount() > 0) {
        throw new Exception('Email already used by another user');
    }

    // Update user basic info
    $update_query = "UPDATE users SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        address = :address,
                        updated_at = NOW()
                    WHERE user_id = :user_id";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':first_name', $data['first_name']);
    $update_stmt->bindParam(':last_name', $data['last_name']);
    $update_stmt->bindParam(':email', $data['email']);
    $update_stmt->bindParam(':phone', $data['phone']);

    $address = $data['address'] ?? '';
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':user_id', $employee_id);

    $update_stmt->execute();

    // Get role name first for validation
    $role_query = "SELECT role_name FROM roles WHERE role_id = :role_id";
    $role_get_stmt = $conn->prepare($role_query);
    $role_get_stmt->bindParam(':role_id', $data['role_id']);
    $role_get_stmt->execute();
    $role = $role_get_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        throw new Exception('Invalid role');
    }

    // Security: Only owners can assign Owner role
    if ($role['role_name'] == 'Owner' && $_SESSION['user_type'] != 'owner') {
        throw new Exception('Only owners can assign the Owner role');
    }

    // Update role - first delete existing role
    $delete_role = "DELETE FROM user_roles WHERE user_id = :user_id";
    $delete_stmt = $conn->prepare($delete_role);
    $delete_stmt->bindParam(':user_id', $employee_id);
    $delete_stmt->execute();

    // Insert new role
    $insert_role = "INSERT INTO user_roles (user_id, role_id, assigned_at)
                    VALUES (:user_id, :role_id, NOW())";
    $role_stmt = $conn->prepare($insert_role);
    $role_stmt->bindParam(':user_id', $employee_id);
    $role_stmt->bindParam(':role_id', $data['role_id']);
    $role_stmt->execute();

    if ($role) {
        $user_type = strtolower($role['role_name']);
        if (!in_array($user_type, ['owner', 'staff', 'customer'])) {
            $user_type = 'staff';
        }

        // Update user_type
        $type_update = "UPDATE users SET user_type = :user_type WHERE user_id = :user_id";
        $type_stmt = $conn->prepare($type_update);
        $type_stmt->bindParam(':user_type', $user_type);
        $type_stmt->bindParam(':user_id', $employee_id);
        $type_stmt->execute();
    }

    // Log activity
    try {
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->log('UPDATE_EMPLOYEE', "Updated employee: {$data['first_name']} {$data['last_name']}");
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }

    $conn->commit();

    // Update session if user is updating their own profile
    $should_reload_page = false;
    if ($employee_id == $_SESSION['user_id']) {
        $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
        $should_reload_page = true;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully',
        'reload_page' => $should_reload_page
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Update Employee Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
