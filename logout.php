<?php
session_start();

// Log logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'includes/ActivityLogger.php';

    try {
        $database = new Database();
        $conn = $database->getConnection();
        $logger = new ActivityLogger($conn, $_SESSION['user_id']);
        $logger->logLogout();
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

session_destroy();
header('Location: index.php');
exit();
?>