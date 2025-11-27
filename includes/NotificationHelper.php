<?php
/**
 * Notification Helper Class
 * Simplifies sending notifications to users
 */

class NotificationHelper {
    /**
     * Send a notification to a user
     *
     * @param int $userId User ID to send notification to
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $link Optional link to redirect
     * @param string $icon Optional icon class
     * @return bool Success status
     */
    public static function send($userId, $type, $title, $message, $link = null, $icon = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "INSERT INTO notifications (user_id, type, title, message, link, icon)
                      VALUES (:user_id, :type, :title, :message, :link, :icon)";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':link', $link);
            $stmt->bindParam(':icon', $icon);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple users
     *
     * @param array $userIds Array of user IDs
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $link Optional link
     * @param string $icon Optional icon
     * @return int Number of notifications sent
     */
    public static function sendToMultiple($userIds, $type, $title, $message, $link = null, $icon = null) {
        $count = 0;
        foreach ($userIds as $userId) {
            if (self::send($userId, $type, $title, $message, $link, $icon)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Send notification to all users with specific role
     *
     * @param string $roleName Role name (Owner, Manager, etc.)
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $link Optional link
     * @param string $icon Optional icon
     * @return int Number of notifications sent
     */
    public static function sendToRole($roleName, $type, $title, $message, $link = null, $icon = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            // Get all users with this role
            $query = "SELECT DISTINCT u.user_id
                      FROM users u
                      INNER JOIN user_roles ur ON u.user_id = ur.user_id
                      INNER JOIN roles r ON ur.role_id = r.role_id
                      WHERE r.role_name = :role_name";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':role_name', $roleName);
            $stmt->execute();

            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return self::sendToMultiple($userIds, $type, $title, $message, $link, $icon);

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unread notification count for user
     *
     * @param int $userId User ID
     * @return int Unread count
     */
    public static function getUnreadCount($userId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "SELECT COUNT(*) as count
                      FROM notifications
                      WHERE user_id = :user_id AND is_read = 0";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent notifications for user
     *
     * @param int $userId User ID
     * @param int $limit Number of notifications to retrieve
     * @return array Notifications
     */
    public static function getRecent($userId, $limit = 10) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "SELECT *
                      FROM notifications
                      WHERE user_id = :user_id
                      ORDER BY created_at DESC
                      LIMIT :limit";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId Notification ID
     * @return bool Success status
     */
    public static function markAsRead($notificationId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $notificationId);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId User ID
     * @return bool Success status
     */
    public static function markAllAsRead($userId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete notification
     *
     * @param int $notificationId Notification ID
     * @return bool Success status
     */
    public static function delete($notificationId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $conn = $database->getConnection();

            $query = "DELETE FROM notifications WHERE notification_id = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $notificationId);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Predefined notification types
     */
    const ORDER_CREATED = 'ORDER_CREATED';
    const ORDER_STATUS_CHANGED = 'ORDER_STATUS_CHANGED';
    const PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    const NEW_MESSAGE = 'NEW_MESSAGE';
    const LOW_STOCK_ALERT = 'LOW_STOCK_ALERT';
    const ORDER_ASSIGNED = 'ORDER_ASSIGNED';
    const EXPENSE_APPROVAL_NEEDED = 'EXPENSE_APPROVAL_NEEDED';
    const NEW_RATING = 'NEW_RATING';
    const APPOINTMENT_BOOKED = 'APPOINTMENT_BOOKED';
    const APPOINTMENT_APPROVED = 'APPOINTMENT_APPROVED';
    const APPOINTMENT_REJECTED = 'APPOINTMENT_REJECTED';
    const APPOINTMENT_REMINDER = 'APPOINTMENT_REMINDER';

    /**
     * Get icon for notification type
     *
     * @param string $type Notification type
     * @return string Icon class
     */
    public static function getIcon($type) {
        $icons = [
            self::ORDER_CREATED => '📦',
            self::ORDER_STATUS_CHANGED => '🔄',
            self::PAYMENT_RECEIVED => '💰',
            self::NEW_MESSAGE => '💬',
            self::LOW_STOCK_ALERT => '⚠️',
            self::ORDER_ASSIGNED => '👨‍🔧',
            self::EXPENSE_APPROVAL_NEEDED => '📝',
            self::NEW_RATING => '⭐',
            self::APPOINTMENT_BOOKED => '📅',
            self::APPOINTMENT_APPROVED => '✅',
            self::APPOINTMENT_REJECTED => '❌',
            self::APPOINTMENT_REMINDER => '🔔'
        ];

        return $icons[$type] ?? '🔔';
    }
}
?>
