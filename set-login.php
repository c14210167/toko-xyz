<?php
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_name'] = 'John Doe';
header('Location: index.php');
?>