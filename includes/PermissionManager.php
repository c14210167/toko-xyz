<?php
/**
 * Permission Manager Class (Updated for RBAC)
 * Compatible with old code while supporting new RBAC system
 */

class PermissionManager {
    private $conn;
    private $user_id;
    private $permissions_cache = null;
    private $roles_cache = null;

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
     * Load all permissions for user (Updated for RBAC)
     */
    private function loadPermissions() {
        $this->permissions_cache = [];

        // First, check if user is owner - owners get ALL permissions
        try {
            $user_query = "SELECT user_type FROM users WHERE user_id = :user_id";
            $user_stmt = $this->conn->prepare($user_query);
            $user_stmt->bindParam(':user_id', $this->user_id);
            $user_stmt->execute();
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

            // If owner, grant ALL permissions automatically
            if ($user && $user['user_type'] == 'owner') {
                $this->permissions_cache = [
                    'view_dashboard', 'view_analytics', 'view_all_orders', 'view_own_orders',
                    'create_order', 'create_orders', 'edit_order', 'edit_orders', 'update_order_status', 'assign_technician', 'cancel_order',
                    'access_pos', 'create_transaction', 'void_transaction', 'give_discount',
                    'view_inventory', 'edit_inventory', 'add_product', 'transfer_stock', 'stock_opname',
                    'view_financial_report', 'view_revenue', 'view_expenses', 'manage_expenses',
                    'view_price_list', 'edit_price_list', 'manage_promotions',
                    'view_staff_list', 'add_staff', 'edit_staff', 'delete_staff',
                    'manage_roles', 'assign_roles', 'manage_permissions',
                    'view_all_locations', 'manage_locations',
                    'view_all_messages', 'reply_messages',
                    'export_reports', 'view_sales_report', 'view_service_report',
                    'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
                    'view_products', 'create_products', 'edit_products', 'delete_products',
                    'view_sales', 'view_reports', 'manage_employees',
                    'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
                    'record_inventory_transaction', 'view_inventory_transactions'
                ];
                return; // Skip RBAC lookup for owners
            }
        } catch (PDOException $e) {
            // Continue with RBAC lookup if user check fails
        }

        // For non-owners, load permissions from RBAC
        try {
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

            // Get user-specific permission overrides
            $override_query = "SELECT p.permission_key, up.is_granted
                              FROM user_permissions up
                              JOIN permissions p ON up.permission_id = p.permission_id
                              WHERE up.user_id = :user_id";
            $override_stmt = $this->conn->prepare($override_query);
            $override_stmt->bindParam(':user_id', $this->user_id);
            $override_stmt->execute();

            while ($row = $override_stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['is_granted'] == 1) {
                    // Add permission if granted
                    if (!in_array($row['permission_key'], $this->permissions_cache)) {
                        $this->permissions_cache[] = $row['permission_key'];
                    }
                } else {
                    // Remove permission if revoked
                    $this->permissions_cache = array_diff($this->permissions_cache, [$row['permission_key']]);
                }
            }

        } catch (PDOException $e) {
            // If RBAC tables don't exist, permissions remain empty array
            error_log("RBAC Error: " . $e->getMessage());
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
        if ($this->roles_cache !== null) {
            return $this->roles_cache;
        }

        try {
            $query = "SELECT r.* FROM user_roles ur
                     JOIN roles r ON ur.role_id = r.role_id
                     WHERE ur.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();
            $this->roles_cache = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->roles_cache = [];
        }

        return $this->roles_cache;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roleName) {
        $roles = $this->getUserRoles();
        foreach ($roles as $role) {
            if ($role['role_name'] === $roleName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is Owner
     */
    public function isOwner() {
        return $this->hasRole('Owner');
    }

    /**
     * Check if user is Manager
     */
    public function isManager() {
        return $this->hasRole('Manager');
    }

    /**
     * Check if user is Technician
     */
    public function isTechnician() {
        return $this->hasRole('Technician');
    }

    /**
     * Check if user is Cashier
     */
    public function isCashier() {
        return $this->hasRole('Cashier');
    }

    /**
     * Check permission and redirect if not authorized
     */
    public function requirePermission($permissionKey, $redirectUrl = '../index.php') {
        if (!$this->hasPermission($permissionKey)) {
            $_SESSION['error'] = 'You do not have permission to access this resource.';
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Check any permission and redirect if not authorized
     */
    public function requireAnyPermission($permissionKeys, $redirectUrl = '../index.php') {
        if (!$this->hasAnyPermission($permissionKeys)) {
            $_SESSION['error'] = 'You do not have permission to access this resource.';
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Get permissions grouped by category
     */
    public function getPermissionsByCategory() {
        $this->loadPermissions();
        $grouped = [];

        try {
            $query = "SELECT p.permission_key, p.permission_name, p.category
                     FROM permissions p
                     WHERE p.permission_key IN ('" . implode("','", $this->permissions_cache) . "')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $category = $row['category'] ?? 'other';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][$row['permission_key']] = $row['permission_name'];
            }
        } catch (PDOException $e) {
            // Ignore if permissions table doesn't exist
        }

        return $grouped;
    }
}
?>