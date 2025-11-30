<?php
/**
 * Migration: Add discount column to order_costs table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add discount column
    $sql = "ALTER TABLE order_costs ADD COLUMN discount DECIMAL(15,2) DEFAULT 0.00 AFTER service_cost";
    $conn->exec($sql);

    echo "✅ SUCCESS: discount column added to order_costs table\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "ℹ️  INFO: discount column already exists\n";
    } else {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}
?>
