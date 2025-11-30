<?php
/**
 * Migration: Add opening_notes and closing_notes columns to pos_sessions table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add opening_notes column
    try {
        $sql1 = "ALTER TABLE pos_sessions ADD COLUMN opening_notes TEXT AFTER notes";
        $conn->exec($sql1);
        echo "✅ SUCCESS: opening_notes column added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  INFO: opening_notes column already exists\n";
        } else {
            throw $e;
        }
    }

    // Add closing_notes column
    try {
        $sql2 = "ALTER TABLE pos_sessions ADD COLUMN closing_notes TEXT AFTER opening_notes";
        $conn->exec($sql2);
        echo "✅ SUCCESS: closing_notes column added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  INFO: closing_notes column already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
