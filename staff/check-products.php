<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Check products count
$query = "SELECT
    COUNT(*) as total_products,
    SUM(CASE WHEN is_active = 1 AND quantity > 0 THEN 1 ELSE 0 END) as active_with_stock,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
    SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as products_with_stock
FROM products";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Products Status</h2>";
echo "<pre>";
echo "Total Products: " . $result['total_products'] . "\n";
echo "Active Products: " . $result['active_products'] . "\n";
echo "Products with Stock: " . $result['products_with_stock'] . "\n";
echo "Active Products with Stock (shown in POS): " . $result['active_with_stock'] . "\n";
echo "</pre>";

// Show sample products
echo "<h3>Sample Products:</h3>";
$sample_query = "SELECT product_id, name, sku, quantity, is_active, selling_price
                 FROM products
                 LIMIT 10";
$sample_stmt = $conn->prepare($sample_query);
$sample_stmt->execute();
$products = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>SKU</th><th>Quantity</th><th>Active</th><th>Price</th></tr>";
foreach ($products as $product) {
    echo "<tr>";
    echo "<td>" . $product['product_id'] . "</td>";
    echo "<td>" . $product['name'] . "</td>";
    echo "<td>" . $product['sku'] . "</td>";
    echo "<td>" . $product['quantity'] . "</td>";
    echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>Rp " . number_format($product['selling_price'], 0, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
