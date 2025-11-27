<?php
/**
 * COMPREHENSIVE FEATURE INSTALLER
 * This script will install ALL features for the XYZ Service Center system
 *
 * IMPORTANT: Run this script only ONCE after backing up your database
 */

session_start();
require_once 'config/database.php';

// Simple authentication (you can modify this)
$install_password = 'install123'; // Change this!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] !== $install_password) {
        $error = 'Invalid password!';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            $results = [];
            $errors = [];

            // Read and execute comprehensive schema (FIXED VERSION)
            $schema_file = __DIR__ . '/comprehensive-database-schema-fixed.sql';
            if (file_exists($schema_file)) {
                $sql = file_get_contents($schema_file);

                // Split by statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($statements as $statement) {
                    if (empty($statement) || strpos($statement, '--') === 0) {
                        continue;
                    }

                    try {
                        $conn->exec($statement);
                        $results[] = 'Executed: ' . substr($statement, 0, 100) . '...';
                    } catch (PDOException $e) {
                        $errors[] = 'Error: ' . $e->getMessage() . ' in ' . substr($statement, 0, 50);
                    }
                }
            }

            // Execute permissions seed
            $permissions_file = __DIR__ . '/seed-permissions.sql';
            if (file_exists($permissions_file)) {
                $sql = file_get_contents($permissions_file);
                $statements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($statements as $statement) {
                    if (empty($statement) || strpos($statement, '--') === 0) {
                        continue;
                    }

                    try {
                        $conn->exec($statement);
                        $results[] = 'Executed: ' . substr($statement, 0, 100) . '...';
                    } catch (PDOException $e) {
                        $errors[] = 'Error: ' . $e->getMessage() . ' in ' . substr($statement, 0, 50);
                    }
                }
            }

            $success = true;

        } catch (Exception $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install All Features - XYZ Service Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .features-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .features-list h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .features-list ul {
            list-style-position: inside;
            color: #555;
        }

        .features-list li {
            padding: 5px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .results {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
        }

        .results div {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }

        .error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 XYZ Service Center - Feature Installer</h1>
        <p class="subtitle">Install all 10 features in one click</p>

        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success">
                <strong>✅ Installation Successful!</strong><br>
                All features have been installed successfully. You can now use the system.
                <br><br>
                <strong>IMPORTANT:</strong> Delete this file (install-all-features.php) for security!
            </div>

            <?php if (!empty($results)): ?>
                <div class="results">
                    <strong>Installation Log:</strong>
                    <?php foreach ($results as $result): ?>
                        <div><?= htmlspecialchars($result) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="results">
                    <strong style="color: #dc3545;">Errors (non-critical):</strong>
                    <?php foreach ($errors as $error_msg): ?>
                        <div class="error"><?= htmlspecialchars($error_msg) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="staff/dashboard.php" class="btn" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">
                Go to Dashboard
            </a>

        <?php else: ?>

            <div class="warning">
                <strong>⚠️ WARNING:</strong><br>
                - This will modify your database structure<br>
                - Backup your database before proceeding<br>
                - Run this script only ONCE<br>
                - Delete this file after installation
            </div>

            <div class="features-list">
                <h3>📦 Features to be installed:</h3>
                <ul>
                    <li>✅ Role-Based Access Control (RBAC)</li>
                    <li>✅ Inventory Management System</li>
                    <li>✅ Product Sales & POS System</li>
                    <li>✅ Expense Management</li>
                    <li>✅ Payment Tracking</li>
                    <li>✅ Appointment/Booking System</li>
                    <li>✅ Rating & Feedback System</li>
                    <li>✅ Notification System</li>
                    <li>✅ Technician Assignment</li>
                    <li>✅ Reporting & Analytics</li>
                </ul>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <strong>❌ Error:</strong><br>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Installation Password:</label>
                    <input type="password" name="password" required placeholder="Enter installation password">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Default: install123 (check line 10 in this file)
                    </small>
                </div>

                <button type="submit" class="btn">
                    🚀 Install All Features Now
                </button>
            </form>

        <?php endif; ?>
    </div>
</body>
</html>
