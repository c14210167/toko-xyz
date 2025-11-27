<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/init_permissions.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check permission
if (!hasPermission('view_inventory')) {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$changes = $data['changes'] ?? [];

if (empty($changes)) {
    echo json_encode(['success' => false, 'message' => 'No changes provided']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();

    $updated_count = 0;
    $errors = [];

    foreach ($changes as $change) {
        $item_id = $change['item_id'];
        $new_quantity = $change['new_quantity'];
        $new_shelf_location = $change['new_shelf_location'] ?? '';

        // Update inventory item
        $update_query = "UPDATE inventory_items
                         SET quantity = :quantity,
                             shelf_location = :shelf_location,
                             updated_at = NOW()
                         WHERE item_id = :item_id";

        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':shelf_location', $new_shelf_location);
        $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $updated_count++;

            // Log activity
            require_once '../../includes/ActivityLogger.php';
            try {
                $logger = new ActivityLogger($conn, $_SESSION['user_id']);

                // Build description
                $description_parts = [];

                // Stock change
                if ($change['old_quantity'] != $new_quantity) {
                    $diff = $new_quantity - $change['old_quantity'];
                    $diff_text = $diff > 0 ? "+{$diff}" : $diff;
                    $description_parts[] = "stock {$change['old_quantity']} → {$new_quantity} ({$diff_text})";
                }

                // Shelf location change
                $old_shelf = $change['old_shelf_location'] ?? '';
                if ($old_shelf != $new_shelf_location) {
                    $old_display = $old_shelf ? "'{$old_shelf}'" : 'not set';
                    $new_display = $new_shelf_location ? "'{$new_shelf_location}'" : 'not set';
                    $description_parts[] = "shelf location {$old_display} → {$new_display}";
                }

                if (!empty($description_parts)) {
                    $description = "Updated inventory for '{$change['item_name']}': " . implode(', ', $description_parts);
                    $logger->log('inventory_update', $description, 'inventory_item', $item_id);
                }
            } catch (Exception $e) {
                error_log("Activity logging error: " . $e->getMessage());
            }
        } else {
            $errors[] = "Failed to update item ID: {$item_id}";
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated {$updated_count} items",
        'updated_count' => $updated_count,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
