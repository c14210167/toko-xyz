<?php
/**
 * Quick Database Setup for Orders Feature
 * Access this file once to update database structure
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #06b6d4; margin-top: 0; }
        h2 { color: #333; border-bottom: 2px solid #06b6d4; padding-bottom: 10px; }
        .success {
            padding: 12px;
            background: #d1fae5;
            border-left: 4px solid #10b981;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error {
            padding: 12px;
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info {
            padding: 12px;
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning {
            padding: 12px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            margin: 10px 0;
            border-radius: 4px;
        }
        code {
            background: #1e293b;
            color: #06b6d4;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .step {
            background: #f8fafc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 3px solid #06b6d4;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #06b6d4;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0891b2;
        }
        pre {
            background: #1e293b;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Database Setup - Orders Feature</h1>
";

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<div class='success'>✅ Database connection successful!</div>";

    $updates = [];
    $errors = [];

    // Step 1: Check and update messages table
    echo "<div class='step'><h2>Step 1: Update Messages Table</h2>";

    // Check receiver_id column
    $check_receiver = $conn->query("SHOW COLUMNS FROM messages LIKE 'receiver_id'");
    if ($check_receiver->rowCount() == 0) {
        try {
            $conn->exec("ALTER TABLE messages ADD COLUMN receiver_id INT NULL AFTER sender_id");
            $updates[] = "✅ Added column: receiver_id";
            echo "<div class='success'>✅ Added column: receiver_id</div>";
        } catch (Exception $e) {
            $errors[] = "❌ Failed to add receiver_id: " . $e->getMessage();
            echo "<div class='error'>❌ Failed to add receiver_id: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Column receiver_id already exists</div>";
    }

    // Check is_read column
    $check_read = $conn->query("SHOW COLUMNS FROM messages LIKE 'is_read'");
    if ($check_read->rowCount() == 0) {
        try {
            $conn->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message");
            $updates[] = "✅ Added column: is_read";
            echo "<div class='success'>✅ Added column: is_read</div>";
        } catch (Exception $e) {
            $errors[] = "❌ Failed to add is_read: " . $e->getMessage();
            echo "<div class='error'>❌ Failed to add is_read: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Column is_read already exists</div>";
    }

    // Add indexes
    try {
        $conn->exec("ALTER TABLE messages ADD INDEX idx_receiver (receiver_id)");
        $updates[] = "✅ Added index: idx_receiver";
        echo "<div class='success'>✅ Added index: idx_receiver</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ️ Index idx_receiver already exists</div>";
    }

    echo "</div>";

    // Step 2: Create order_status_history table
    echo "<div class='step'><h2>Step 2: Create order_status_history Table</h2>";

    try {
        $create_history = "CREATE TABLE IF NOT EXISTS order_status_history (
            history_id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_changed_by (changed_by),
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE CASCADE
        )";

        $conn->exec($create_history);

        // Check if table was created
        $check_table = $conn->query("SHOW TABLES LIKE 'order_status_history'");
        if ($check_table->rowCount() > 0) {
            $updates[] = "✅ Table order_status_history created";
            echo "<div class='success'>✅ Table order_status_history created/verified</div>";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Failed to create order_status_history: " . $e->getMessage();
        echo "<div class='error'>❌ Failed to create order_status_history: " . $e->getMessage() . "</div>";
    }

    echo "</div>";

    // Step 3: Add indexes to orders table
    echo "<div class='step'><h2>Step 3: Add Performance Indexes</h2>";

    $indexes = [
        'idx_orders_status' => 'status',
        'idx_orders_created' => 'created_at',
        'idx_orders_updated' => 'updated_at'
    ];

    foreach ($indexes as $index_name => $column) {
        try {
            $conn->exec("ALTER TABLE orders ADD INDEX $index_name ($column)");
            echo "<div class='success'>✅ Added index: $index_name</div>";
        } catch (Exception $e) {
            echo "<div class='info'>ℹ️ Index $index_name already exists</div>";
        }
    }

    echo "</div>";

    // Show current messages table structure
    echo "<div class='step'><h2>Current Messages Table Structure</h2>";
    $stmt = $conn->query("DESCRIBE messages");
    echo "<pre>";
    echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . "Default\n";
    echo str_repeat("-", 60) . "\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) .
             str_pad($row['Type'], 20) .
             str_pad($row['Null'], 10) .
             ($row['Default'] ?? 'NULL') . "\n";
    }
    echo "</pre></div>";

    // Summary
    echo "<div class='step'>";
    echo "<h2>📊 Setup Summary</h2>";

    if (!empty($updates)) {
        echo "<div class='success'>";
        echo "<strong>✅ Successful Updates:</strong><ul>";
        foreach ($updates as $update) {
            echo "<li>$update</li>";
        }
        echo "</ul></div>";
    }

    if (!empty($errors)) {
        echo "<div class='error'>";
        echo "<strong>❌ Errors:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
    }

    if (empty($errors)) {
        echo "<div class='success'>";
        echo "<h3>🎉 Setup Complete!</h3>";
        echo "<p>Database is ready for Orders Management feature.</p>";
        echo "<a href='orders.php' class='btn'>Go to Orders Page</a>";
        echo "<a href='dashboard.php' class='btn'>Go to Dashboard</a>";
        echo "</div>";

        echo "<div class='warning'>";
        echo "<strong>⚠️ Security:</strong> Delete this file after setup: <code>staff/setup-database.php</code>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Some errors occurred</h3>";
        echo "<p>Please fix the errors above, or run the SQL manually in phpMyAdmin.</p>";
        echo "</div>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "
    <div class='info' style='margin-top: 30px;'>
        <h3>📚 Manual Setup (if needed)</h3>
        <p>If automatic setup fails, run this SQL in phpMyAdmin:</p>
        <pre>-- Update messages table
ALTER TABLE messages
ADD COLUMN receiver_id INT NULL AFTER sender_id,
ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message,
ADD INDEX idx_receiver (receiver_id);

-- Create order_status_history table
CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (order_id),
    INDEX idx_changed_by (changed_by),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Add indexes for performance
ALTER TABLE orders ADD INDEX idx_orders_status (status);
ALTER TABLE orders ADD INDEX idx_orders_created (created_at);
ALTER TABLE orders ADD INDEX idx_orders_updated (updated_at);</pre>
    </div>
</div>
</body>
</html>";
?>
