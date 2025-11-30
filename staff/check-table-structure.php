<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_type'] != 'owner') {
    die("Only owner can run this script");
}

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Database Table Structures</h2>";

// Check inventory_items structure
echo "<h3>inventory_items Table Structure:</h3>";
$query1 = "DESCRIBE inventory_items";
$stmt1 = $conn->prepare($query1);
$stmt1->execute();
$columns1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns1 as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

// Check products structure
echo "<h3>products Table Structure:</h3>";
$query2 = "DESCRIBE products";
$stmt2 = $conn->prepare($query2);
$stmt2->execute();
$columns2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns2 as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
}
echo "</table>";

// Show sample data from inventory_items
echo "<h3>Sample Data from inventory_items:</h3>";
$query3 = "SELECT * FROM inventory_items LIMIT 3";
$stmt3 = $conn->prepare($query3);
$stmt3->execute();
$samples = $stmt3->fetchAll(PDO::FETCH_ASSOC);

if (count($samples) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    foreach (array_keys($samples[0]) as $key) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    foreach ($samples as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>
