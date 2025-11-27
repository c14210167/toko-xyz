<?php
/**
 * Initialize Permission Manager for the current session
 * Include this file after session_start() and authentication
 */

require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/database.php';

// Initialize Permission Manager if user is logged in
if (isset($_SESSION['user_id'])) {
    // Don't store PDO or PermissionManager in session (causes serialization error)
    // Create fresh instances each time
    $database = new Database();
    $conn = $database->getConnection();
    $permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

    // Helper function to check permission globally
    if (!function_exists('hasPermission')) {
        function hasPermission($permissionKey) {
            global $permissionManager;
            return $permissionManager ? $permissionManager->hasPermission($permissionKey) : false;
        }
    }

    if (!function_exists('hasAnyPermission')) {
        function hasAnyPermission($permissionKeys) {
            global $permissionManager;
            return $permissionManager ? $permissionManager->hasAnyPermission($permissionKeys) : false;
        }
    }

    if (!function_exists('hasRole')) {
        function hasRole($roleName) {
            global $permissionManager;
            return $permissionManager ? $permissionManager->hasRole($roleName) : false;
        }
    }
}
