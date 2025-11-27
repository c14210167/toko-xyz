<?php
/**
 * Activity Logger Helper Class
 * Records all employee activities for audit trail
 */

class ActivityLogger {
    private $conn;
    private $user_id;

    public function __construct($db_connection, $user_id) {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
    }

    /**
     * Log an activity
     *
     * @param string $action_type Type of action (login, logout, order_update, etc.)
     * @param string $action_description Human-readable description
     * @param string $entity_type Optional: Type of related entity (order, role, etc.)
     * @param int $entity_id Optional: ID of related entity
     * @return bool Success status
     */
    public function log($action_type, $action_description, $entity_type = null, $entity_id = null) {
        try {
            // Get IP address
            $ip_address = $this->getClientIP();

            // Get User Agent
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $query = "INSERT INTO activity_logs
                      (user_id, action_type, action_description, related_entity_type, related_entity_id, ip_address, user_agent)
                      VALUES
                      (:user_id, :action_type, :action_description, :entity_type, :entity_id, :ip_address, :user_agent)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':action_type', $action_type);
            $stmt->bindParam(':action_description', $action_description);
            $stmt->bindParam(':entity_type', $entity_type);
            $stmt->bindParam(':entity_id', $entity_id);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Activity Log Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log user login
     */
    public function logLogin() {
        return $this->log('login', 'User logged in');
    }

    /**
     * Log user logout
     */
    public function logLogout() {
        return $this->log('logout', 'User logged out');
    }

    /**
     * Log order status change
     */
    public function logOrderUpdate($order_id, $old_status, $new_status) {
        $description = "Changed order status from '$old_status' to '$new_status'";
        return $this->log('order_update', $description, 'order', $order_id);
    }

    /**
     * Log order creation
     */
    public function logOrderCreate($order_id, $order_number) {
        $description = "Created new order #$order_number";
        return $this->log('order_create', $description, 'order', $order_id);
    }

    /**
     * Log role permission change
     */
    public function logRolePermissionUpdate($role_id, $role_name) {
        $description = "Updated permissions for role '$role_name'";
        return $this->log('permission_change', $description, 'role', $role_id);
    }

    /**
     * Log role creation
     */
    public function logRoleCreate($role_id, $role_name) {
        $description = "Created new role '$role_name'";
        return $this->log('role_create', $description, 'role', $role_id);
    }

    /**
     * Log role deletion
     */
    public function logRoleDelete($role_name) {
        $description = "Deleted role '$role_name'";
        return $this->log('role_delete', $description, 'role', null);
    }

    /**
     * Log employee role assignment
     */
    public function logEmployeeRoleUpdate($employee_id, $employee_name) {
        $description = "Updated roles for employee '$employee_name'";
        return $this->log('employee_update', $description, 'user', $employee_id);
    }

    /**
     * Log location creation
     */
    public function logLocationCreate($location_id, $location_name) {
        $description = "Created new location '$location_name'";
        return $this->log('location_create', $description, 'location', $location_id);
    }

    /**
     * Log location update
     */
    public function logLocationUpdate($location_id, $location_name) {
        $description = "Updated location '$location_name'";
        return $this->log('location_update', $description, 'location', $location_id);
    }

    /**
     * Log location deletion
     */
    public function logLocationDelete($location_name) {
        $description = "Deleted location '$location_name'";
        return $this->log('location_delete', $description, 'location', null);
    }

    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }
        return $ip;
    }

    /**
     * Static method to log activity without instantiating class
     */
    public static function quickLog($db_connection, $user_id, $action_type, $description, $entity_type = null, $entity_id = null) {
        $logger = new self($db_connection, $user_id);
        return $logger->log($action_type, $description, $entity_type, $entity_id);
    }
}
?>
