<?php
/**
 * Ensure Owner Role Has All Permissions
 * Run this script once to fix permission issues
 */

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    echo "<h2>Ensuring Owner Role Has All Permissions</h2>";

    // Get Owner role
    $role_query = "SELECT role_id FROM roles WHERE role_name = 'Owner' LIMIT 1";
    $role_stmt = $conn->prepare($role_query);
    $role_stmt->execute();
    $owner_role = $role_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner_role) {
        echo "<p style='color: red;'>ERROR: Owner role not found!</p>";
        exit();
    }

    $owner_role_id = $owner_role['role_id'];
    echo "<p>✓ Found Owner role (ID: $owner_role_id)</p>";

    // Get all permissions
    $perms_query = "SELECT permission_id, permission_key, permission_name FROM permissions ORDER BY permission_name";
    $perms_stmt = $conn->prepare($perms_query);
    $perms_stmt->execute();
    $permissions = $perms_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>✓ Found " . count($permissions) . " permissions</p>";

    // Clear existing permissions for Owner
    $clear_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
    $clear_stmt = $conn->prepare($clear_query);
    $clear_stmt->bindParam(':role_id', $owner_role_id);
    $clear_stmt->execute();

    echo "<p>✓ Cleared existing permissions</p>";

    // Assign all permissions to Owner
    $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
    $insert_stmt = $conn->prepare($insert_query);

    $count = 0;
    foreach ($permissions as $permission) {
        $insert_stmt->bindParam(':role_id', $owner_role_id);
        $insert_stmt->bindParam(':permission_id', $permission['permission_id']);
        $insert_stmt->execute();
        $count++;
    }

    echo "<p style='color: green; font-weight: bold;'>✓ SUCCESS: Assigned $count permissions to Owner role!</p>";

    // Show permissions
    echo "<h3>Permissions granted to Owner:</h3>";
    echo "<ul>";
    foreach ($permissions as $permission) {
        echo "<li>{$permission['permission_name']} ({$permission['permission_key']})</li>";
    }
    echo "</ul>";

    echo "<p><a href='staff/dashboard.php'>← Back to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
