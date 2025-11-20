<?php
session_start();

// Simulate logged in staff user for testing
if (!isset($_SESSION['user_logged_in'])) {
    echo "<h2>Setting up test session...</h2>";
    echo "<p>Please login first as staff/owner, then return to this page.</p>";
    echo "<a href='../login.php'>Go to Login</a>";
    exit();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Orders API</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .section {
            background: #252526;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #3e3e42;
        }
        h2 { color: #4fc3f7; }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
        }
        .success { color: #4caf50; }
        .error { color: #f44336; }
        .info { color: #2196f3; }
    </style>
</head>
<body>
    <h1>🧪 Orders API Test</h1>

    <div class='section'>
        <h2>Session Info</h2>
        <pre>" . print_r($_SESSION, true) . "</pre>
    </div>

    <div class='section'>
        <h2>API Response Test</h2>
        <p>Testing: <code>get-orders.php</code></p>
";

// Test the API
$url = 'http://localhost/frontendproject/staff/get-orders.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> <span class='" . ($http_code == 200 ? 'success' : 'error') . "'>$http_code</span></p>";

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>Decoded JSON:</h3>";
$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre class='success'>" . print_r($json, true) . "</pre>";
} else {
    echo "<p class='error'>❌ JSON Parse Error: " . json_last_error_msg() . "</p>";
    echo "<p class='info'>This is the problem! The response contains invalid JSON.</p>";
}

echo "
    </div>

    <div class='section'>
        <h2>Database Tables Check</h2>
";

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Check messages table structure
echo "<h3>Messages Table Structure:</h3>";
$stmt = $conn->query("DESCRIBE messages");
echo "<pre>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "</pre>";

// Check if we have orders
echo "<h3>Orders Count:</h3>";
$count = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
echo "<p>Total Orders: <strong>$count</strong></p>";

echo "
    </div>

    <div class='section'>
        <h2>Next Steps</h2>
        <ul>
            <li>If JSON error exists, run <a href='../setup-orders-feature.php' style='color: #4fc3f7;'>setup-orders-feature.php</a></li>
            <li>Check if 'is_read' column exists in messages table</li>
            <li>Try <a href='orders.php' style='color: #4fc3f7;'>orders.php</a> again</li>
        </ul>
    </div>
</body>
</html>";
?>
