<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['user_type'] == 'customer') {
    header('Location: ../index.php');
    exit();
}

// Set flag untuk view as customer
$_SESSION['viewing_as_customer'] = true;

header('Location: ../index.php');
exit();
?>