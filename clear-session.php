<?php
/**
 * Clear Session Script
 * Run this once to clear old session data that causes serialization errors
 */

session_start();

// Clear problematic session variables
unset($_SESSION['permissionManager']);
unset($_SESSION['db_connection']);
unset($_SESSION['user_roles']);

// Optionally clear entire session
// session_destroy();

echo "✅ Session cleared successfully!<br><br>";
echo "Old session variables removed:<br>";
echo "- permissionManager<br>";
echo "- db_connection<br>";
echo "- user_roles<br><br>";
echo "<strong>You can now login again.</strong><br><br>";
echo '<a href="login.php">Go to Login</a>';
?>
