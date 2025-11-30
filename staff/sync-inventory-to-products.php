<?php
/**
 * Sync Inventory Items to Products Table
 * Copies data from inventory_items to products table for POS usage
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
    echo "<h2>Syncing Inventory to Products</h2>";

    // Check current status
    $check_inventory = "SELECT COUNT(*) as count FROM inventory_items";
    $check_products = "SELECT COUNT(*) as count FROM products";

    $stmt = $conn->prepare($check_inventory);
    $stmt->execute();
    $inventory_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $conn->prepare($check_products);
    $stmt->execute();
    $products_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "<p>Current inventory_items: <strong>{$inventory_count}</strong></p>";
    echo "<p>Current products: <strong>{$products_count}</strong></p>";

    if ($inventory_count == 0) {
        echo "<p style='color: red;'>No items in inventory_items table. Please add items first.</p>";
        exit();
    }

    // Sync inventory_items to products
    $sync_query = "INSERT INTO products (name, sku, category_id, quantity, cost_price, selling_price, is_active, created_at, updated_at)
                   SELECT
                       i.name,
                       COALESCE(i.item_code, CONCAT('SKU-', i.item_id)) as sku,
                       i.category_id,
                       i.quantity,
                       i.unit_price as cost_price,
                       ROUND(i.unit_price * 1.3, 2) as selling_price,
                       1 as is_active,
                       NOW() as created_at,
                       NOW() as updated_at
                   FROM inventory_items i
                   WHERE NOT EXISTS (
                       SELECT 1 FROM products p
                       WHERE p.sku = COALESCE(i.item_code, CONCAT('SKU-', i.item_id))
                   )";

    $sync_stmt = $conn->prepare($sync_query);
    $sync_stmt->execute();
    $synced = $sync_stmt->rowCount();

    echo "<h3>Sync Results:</h3>";
    echo "<p><strong>{$synced}</strong> items synchronized from inventory_items to products.</p>";

    // Show updated count
    $stmt = $conn->prepare($check_products);
    $stmt->execute();
    $products_count_after = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "<p>Products table now has: <strong>{$products_count_after}</strong> items</p>";

    // Show sample products
    $sample_query = "SELECT product_id, name, sku, quantity, selling_price, is_active
                     FROM products
                     ORDER BY product_id DESC
                     LIMIT 10";
    $sample_stmt = $conn->prepare($sample_query);
    $sample_stmt->execute();
    $sample_products = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Sample Products (Latest 10):</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Qty</th><th>Price</th><th>Active</th></tr>";
    foreach ($sample_products as $product) {
        echo "<tr>";
        echo "<td>" . $product['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['sku']) . "</td>";
        echo "<td>" . $product['quantity'] . "</td>";
        echo "<td>Rp " . number_format($product['selling_price'], 0, ',', '.') . "</td>";
        echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p style='margin-top: 20px;'>";
    echo "<a href='pos.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Go to POS</a> ";
    echo "<a href='inventory.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Inventory</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
