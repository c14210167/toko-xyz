<?php
/**
 * Add qris_sales column to pos_sessions table
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_type'] != 'owner') {
    die("Only owner can run this script");
}

$database = new Database();
$conn = $database->getConnection();

try {
    echo "<h2>Adding qris_sales Column to pos_sessions</h2>";

    // Check if column already exists
    $check_query = "SHOW COLUMNS FROM pos_sessions LIKE 'qris_sales'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute();
    $exists = $check_stmt->fetch();

    if ($exists) {
        echo "<p style='color: orange;'>Column 'qris_sales' already exists.</p>";
    } else {
        // Add qris_sales column after card_sales
        $alter_query = "ALTER TABLE pos_sessions
                       ADD COLUMN qris_sales DECIMAL(15,2) DEFAULT 0.00 AFTER card_sales";
        $conn->exec($alter_query);
        echo "<p style='color: green;'>✓ Column 'qris_sales' added successfully!</p>";
    }

    // Show table structure
    echo "<h3>Updated pos_sessions Structure:</h3>";
    $show_query = "SHOW COLUMNS FROM pos_sessions";
    $show_stmt = $conn->prepare($show_query);
    $show_stmt->execute();
    $columns = $show_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p style='margin-top: 20px;'>";
    echo "<a href='pos.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>Go to POS</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
