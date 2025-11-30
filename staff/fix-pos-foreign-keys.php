<?php
/**
 * Fix POS Foreign Keys
 * Update pos_transaction_items to reference inventory_items instead of products
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_type'] != 'owner') {
    die("Only owner can run this script");
}

$database = new Database();
$conn = $database->getConnection();

try {
    echo "<h2>Fixing POS Foreign Key Constraints</h2>";

    // Drop existing foreign key constraint
    echo "<p>1. Dropping old foreign key constraint...</p>";
    $drop_fk = "ALTER TABLE pos_transaction_items
                DROP FOREIGN KEY pos_transaction_items_ibfk_2";

    try {
        $conn->exec($drop_fk);
        echo "<p style='color: green;'>✓ Old foreign key dropped successfully.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Foreign key may not exist or already dropped: " . $e->getMessage() . "</p>";
    }

    // Rename product_id column to item_id
    echo "<p>2. Renaming product_id to item_id...</p>";
    $rename_col = "ALTER TABLE pos_transaction_items
                   CHANGE COLUMN product_id item_id INT NOT NULL";

    try {
        $conn->exec($rename_col);
        echo "<p style='color: green;'>✓ Column renamed successfully.</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Column may already be renamed: " . $e->getMessage() . "</p>";
    }

    // Add new foreign key constraint to inventory_items
    echo "<p>3. Adding new foreign key to inventory_items...</p>";
    $add_fk = "ALTER TABLE pos_transaction_items
               ADD CONSTRAINT fk_pos_items_inventory
               FOREIGN KEY (item_id) REFERENCES inventory_items(item_id)
               ON DELETE RESTRICT";

    try {
        $conn->exec($add_fk);
        echo "<p style='color: green;'>✓ New foreign key added successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error adding foreign key: " . $e->getMessage() . "</p>";
    }

    // Show table structure
    echo "<h3>Current pos_transaction_items Structure:</h3>";
    $show_create = "SHOW CREATE TABLE pos_transaction_items";
    $stmt = $conn->prepare($show_create);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($result['Create Table']);
    echo "</pre>";

    echo "<p style='margin-top: 20px;'>";
    echo "<a href='pos.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Go to POS</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
