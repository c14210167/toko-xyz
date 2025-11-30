<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PermissionManager.php';
require_once '../config/init_permissions.php';

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    die("Not logged in");
}

echo "<h2>Debug Kasir Permission</h2>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";

$database = new Database();
$conn = $database->getConnection();
$permissionManager = new PermissionManager($conn, $_SESSION['user_id']);

// Get user roles
$roles = $permissionManager->getUserRoles();
echo "<h3>User Roles:</h3>";
echo "<pre>" . print_r($roles, true) . "</pre>";

// Get all permissions
$all_permissions = $permissionManager->getUserPermissions();
echo "<h3>All User Permissions:</h3>";
echo "<pre>" . print_r($all_permissions, true) . "</pre>";

// Test specific permissions
echo "<h3>Permission Tests:</h3>";
$test_permissions = ['Akses POS', 'Buat Transaksi', 'Lihat Semua Order', 'Lihat Inventory'];
foreach ($test_permissions as $perm) {
    $has = hasPermission($perm);
    echo "<p><strong>$perm:</strong> " . ($has ? '✅ YES' : '❌ NO') . "</p>";
}

echo "<h3>hasAnyPermission Test:</h3>";
$result = hasAnyPermission(['Akses POS', 'Lihat Semua Order', 'Buat Transaksi']);
echo "<p>hasAnyPermission(['Akses POS', 'Lihat Semua Order', 'Buat Transaksi']): " . ($result ? '✅ YES' : '❌ NO') . "</p>";

echo "<h3>requireAnyPermission Test:</h3>";
try {
    $permissionManager->requireAnyPermission(['Akses POS', 'Lihat Semua Order', 'Buat Transaksi'], 'dashboard.php');
    echo "<p>✅ requireAnyPermission passed!</p>";
} catch (Exception $e) {
    echo "<p>❌ requireAnyPermission failed: " . $e->getMessage() . "</p>";
}
?>
