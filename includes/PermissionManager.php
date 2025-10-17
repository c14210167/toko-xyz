<?php
class PermissionManager {
    private $conn;
    private $user_id;
    private $permissions_cache = null;
    
    public function __construct($db_connection, $user_id) {
        $this->conn = $db_connection;
        $this->user_id = $user_id;
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission_key) {
        if ($this->permissions_cache === null) {
            $this->loadPermissions();
        }
        
        return in_array($permission_key, $this->permissions_cache);
    }
    
    /**
     * Check if user has any of the permissions
     */
    public function hasAnyPermission($permission_keys) {
        foreach ($permission_keys as $key) {
            if ($this->hasPermission($key)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all permissions
     */
    public function hasAllPermissions($permission_keys) {
        foreach ($permission_keys as $key) {
            if (!$this->hasPermission($key)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Load all permissions for user
     */
    private function loadPermissions() {
        $this->permissions_cache = [];
        
        // Get user info
        $user_query = "SELECT user_type, has_custom_permissions FROM users WHERE user_id = :user_id";
        $user_stmt = $this->conn->prepare($user_query);
        $user_stmt->bindParam(':user_id', $this->user_id);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return;
        }
        
        // Owner has all permissions
        if ($user['user_type'] == 'owner') {
            $all_perms_query = "SELECT permission_key FROM permissions";
            $all_perms_stmt = $this->conn->prepare($all_perms_query);
            $all_perms_stmt->execute();
            while ($row = $all_perms_stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->permissions_cache[] = $row['permission_key'];
            }
            return;
        }
        
        // If user has custom permissions, use those
        if ($user['has_custom_permissions']) {
            $custom_query = "SELECT p.permission_key, up.is_granted
                           FROM user_permissions up
                           JOIN permissions p ON up.permission_id = p.permission_id
                           WHERE up.user_id = :user_id";
            $custom_stmt = $this->conn->prepare($custom_query);
            $custom_stmt->bindParam(':user_id', $this->user_id);
            $custom_stmt->execute();
            
            while ($row = $custom_stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['is_granted']) {
                    $this->permissions_cache[] = $row['permission_key'];
                }
            }
        } else {
            // Get permissions from roles
            $role_query = "SELECT DISTINCT p.permission_key
                          FROM user_roles ur
                          JOIN role_permissions rp ON ur.role_id = rp.role_id
                          JOIN permissions p ON rp.permission_id = p.permission_id
                          WHERE ur.user_id = :user_id";
            $role_stmt = $this->conn->prepare($role_query);
            $role_stmt->bindParam(':user_id', $this->user_id);
            $role_stmt->execute();
            
            while ($row = $role_stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->permissions_cache[] = $row['permission_key'];
            }
        }
    }
    
    /**
     * Get all permissions for user (for display)
     */
    public function getAllPermissions() {
        if ($this->permissions_cache === null) {
            $this->loadPermissions();
        }
        return $this->permissions_cache;
    }
    
    /**
     * Get user roles
     */
    public function getUserRoles() {
        $query = "SELECT r.* FROM user_roles ur
                 JOIN roles r ON ur.role_id = r.role_id
                 WHERE ur.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>