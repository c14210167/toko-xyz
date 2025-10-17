<?php
// Set the password you want to use
$new_password = "admin";  // Change this to whatever password you want

// Generate the hash
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "New password: " . $new_password . "<br>";
echo "Hash to copy: " . $hash;
?>