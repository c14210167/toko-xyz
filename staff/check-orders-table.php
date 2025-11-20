<?php
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Check Orders Table Structure</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        h1 { color: #06b6d4; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #252526;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #3e3e42;
            text-align: left;
        }
        th {
            background: #1e1e1e;
            color: #06b6d4;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Orders Table Structure</h1>
";

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<h2>Columns in 'orders' table:</h2>";
    $stmt = $conn->query("DESCRIBE orders");

    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show sample data
    echo "<h2>Sample Data (first 3 rows):</h2>";
    $sample = $conn->query("SELECT * FROM orders LIMIT 3");

    if ($sample->rowCount() > 0) {
        echo "<table>";
        $first = true;
        while ($row = $sample->fetch(PDO::FETCH_ASSOC)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in orders table</p>";
    }

    echo "<div class='info'>";
    echo "<h3>Next Step:</h3>";
    echo "<p>Copy the column names above and I will update get-orders.php to match your database structure.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: #ef4444;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
