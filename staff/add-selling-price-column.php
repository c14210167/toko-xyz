<?php
/**
 * Add selling_price column to inventory_items table
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_type'] != 'owner') {
    die("Only owner can run this script");
}

$database = new Database();
$conn = $database->getConnection();

try {
    echo "<h2>Adding selling_price Column to inventory_items</h2>";

    // Check if column already exists
    $check_query = "SHOW COLUMNS FROM inventory_items LIKE 'selling_price'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute();
    $exists = $check_stmt->fetch();

    if ($exists) {
        echo "<p style='color: orange;'>Column 'selling_price' already exists.</p>";
    } else {
        // Add selling_price column
        $alter_query = "ALTER TABLE inventory_items
                       ADD COLUMN selling_price DECIMAL(10,2) DEFAULT NULL AFTER unit_price";
        $conn->exec($alter_query);
        echo "<p style='color: green;'>✓ Column 'selling_price' added successfully!</p>";

        // Update existing items to have selling_price = unit_price * 1.3
        $update_query = "UPDATE inventory_items
                        SET selling_price = ROUND(unit_price * 1.3, 2)
                        WHERE selling_price IS NULL";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute();
        $updated = $update_stmt->rowCount();

        echo "<p style='color: green;'>✓ Updated {$updated} items with calculated selling price (30% markup).</p>";
    }

    // Show sample data
    echo "<h3>Sample Items with Prices:</h3>";
    $sample_query = "SELECT item_id, name, unit_price, selling_price
                     FROM inventory_items
                     LIMIT 5";
    $sample_stmt = $conn->prepare($sample_query);
    $sample_stmt->execute();
    $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Unit Price (Cost)</th><th>Selling Price</th><th>Margin</th></tr>";
    foreach ($samples as $item) {
        $margin = $item['selling_price'] && $item['unit_price'] > 0
            ? round((($item['selling_price'] - $item['unit_price']) / $item['unit_price']) * 100, 1)
            : 0;
        echo "<tr>";
        echo "<td>{$item['item_id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>Rp " . number_format($item['unit_price'], 0, ',', '.') . "</td>";
        echo "<td>Rp " . number_format($item['selling_price'], 0, ',', '.') . "</td>";
        echo "<td>{$margin}%</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p style='margin-top: 20px;'>";
    echo "<a href='inventory.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Inventory</a> ";
    echo "<a href='pos.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Go to POS</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
