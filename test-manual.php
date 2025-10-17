<?php
session_start();

// Fake login untuk testing
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = 9;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_type'] = 'customer';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Test Form</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        input, textarea { display: block; margin: 10px 0; padding: 8px; width: 300px; }
        button { padding: 10px 20px; background: blue; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Manual Test - Create Order</h1>
    <p>Session User ID: <?php echo $_SESSION['user_id']; ?></p>
    
    <form action="process-create-order.php" method="POST">
        <label>Device Type:</label>
        <input type="text" name="device_type" value="laptop" required>
        
        <label>Issue Type:</label>
        <input type="text" name="issue_type" value="Layar mati" required>
        
        <label>Brand:</label>
        <input type="text" name="brand" value="ASUS" required>
        
        <label>Model:</label>
        <input type="text" name="model" value="ROG Test" required>
        
        <label>Serial Number:</label>
        <input type="text" name="serial_number" value="TEST123">
        
        <label>Additional Notes:</label>
        <textarea name="additional_notes">Test manual form</textarea>
        
        <label>
            <input type="checkbox" name="warranty_check" value="1">
            Warranty
        </label>
        
        <button type="submit">SUBMIT TEST</button>
    </form>
</body>
</html>