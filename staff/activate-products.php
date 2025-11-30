<?php
/**
 * Activate all products for POS
 * This script updates all products to is_active = 1
 */

session_start();
require_once '../config/database.php';

// Check if logged in as owner
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_type'] != 'owner') {
    die("Only owner can run this script");
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Update all products to be active
    $update_query = "UPDATE products SET is_active = 1 WHERE is_active = 0 OR is_active IS NULL";
    $stmt = $conn->prepare($update_query);
    $stmt->execute();
    $updated = $stmt->rowCount();

    // Show results
    echo "<h2>Products Activation Complete</h2>";
    echo "<p>Updated {$updated} products to active status.</p>";

    // Show current stats
    $stats_query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as with_stock,
        SUM(CASE WHEN is_active = 1 AND quantity > 0 THEN 1 ELSE 0 END) as active_with_stock
    FROM products";
    $stmt = $conn->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Current Status:</h3>";
    echo "<ul>";
    echo "<li>Total Products: {$stats['total']}</li>";
    echo "<li>Active Products: {$stats['active']}</li>";
    echo "<li>Products with Stock: {$stats['with_stock']}</li>";
    echo "<li><strong>Products visible in POS: {$stats['active_with_stock']}</strong></li>";
    echo "</ul>";

    echo "<p><a href='pos.php'>Go to POS</a> | <a href='inventory.php'>Go to Inventory</a></p>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
