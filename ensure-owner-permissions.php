<?php
/**
 * Script to ensure Owner has all permissions
 * Run this once to fix permission issues
 */

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    echo "Starting permission fix...\n";

    // Get Owner role ID
    $role_query = "SELECT role_id FROM roles WHERE role_name = 'Owner' LIMIT 1";
    $role_stmt = $conn->query($role_query);
    $owner_role = $role_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner_role) {
        echo "Error: Owner role not found!\n";
        exit(1);
    }

    $owner_role_id = $owner_role['role_id'];
    echo "Found Owner role with ID: $owner_role_id\n";

    // Get all permissions
    $perms_query = "SELECT permission_id FROM permissions";
    $perms_stmt = $conn->query($perms_query);
    $all_permissions = $perms_stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($all_permissions) . " permissions\n";

    // Clear existing Owner permissions and re-assign all
    $delete_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':role_id', $owner_role_id);
    $delete_stmt->execute();

    echo "Cleared old permissions\n";

    // Insert all permissions for Owner
    $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
    $insert_stmt = $conn->prepare($insert_query);

    $count = 0;
    foreach ($all_permissions as $perm_id) {
        $insert_stmt->bindParam(':role_id', $owner_role_id);
        $insert_stmt->bindParam(':permission_id', $perm_id);
        $insert_stmt->execute();
        $count++;
    }

    echo "Assigned $count permissions to Owner role\n";
    echo "✓ Success! Owner now has all permissions.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
