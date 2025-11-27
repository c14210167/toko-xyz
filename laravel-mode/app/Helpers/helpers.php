<?php

/**
 * Global Helper Functions
 * Auto-loaded via composer.json
 */

if (!function_exists('hasPermission')) {
    /**
     * Check if current user has a specific permission
     *
     * @param string $permissionKey
     * @return bool
     */
    function hasPermission($permissionKey)
    {
        return auth()->check() && auth()->user()->hasPermission($permissionKey);
    }
}

if (!function_exists('hasRole')) {
    /**
     * Check if current user has a specific role
     *
     * @param string $roleName
     * @return bool
     */
    function hasRole($roleName)
    {
        return auth()->check() && auth()->user()->hasRole($roleName);
    }
}

if (!function_exists('generateOrderNumber')) {
    /**
     * Generate unique order number
     *
     * @return string
     */
    function generateOrderNumber()
    {
        return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateSaleNumber')) {
    /**
     * Generate unique sale number
     *
     * @return string
     */
    function generateSaleNumber()
    {
        return 'SAL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generatePaymentNumber')) {
    /**
     * Generate unique payment number
     *
     * @return string
     */
    function generatePaymentNumber()
    {
        return 'PAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateAppointmentNumber')) {
    /**
     * Generate unique appointment number
     *
     * @return string
     */
    function generateAppointmentNumber()
    {
        return 'APT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format number as Indonesian currency
     *
     * @param float $amount
     * @return string
     */
    function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('getStatusBadgeClass')) {
    /**
     * Get CSS class for order status badge
     *
     * @param string $status
     * @return string
     */
    function getStatusBadgeClass($status)
    {
        return match ($status) {
            'pending' => 'badge-warning',
            'in_progress' => 'badge-info',
            'waiting_parts' => 'badge-secondary',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            default => 'badge-light',
        };
    }
}

if (!function_exists('getPriorityBadgeClass')) {
    /**
     * Get CSS class for priority badge
     *
     * @param string $priority
     * @return string
     */
    function getPriorityBadgeClass($priority)
    {
        return match ($priority) {
            'low' => 'badge-secondary',
            'normal' => 'badge-info',
            'high' => 'badge-warning',
            'urgent' => 'badge-danger',
            default => 'badge-light',
        };
    }
}

if (!function_exists('notifyUser')) {
    /**
     * Create a notification for a user
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $link
     * @param string|null $icon
     * @return void
     */
    function notifyUser($userId, $type, $title, $message, $link = null, $icon = null)
    {
        \App\Models\Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon,
            'is_read' => false,
        ]);
    }
}

if (!function_exists('notifyRole')) {
    /**
     * Create notifications for all users with a specific role
     *
     * @param string $roleName
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $link
     * @param string|null $icon
     * @return void
     */
    function notifyRole($roleName, $type, $title, $message, $link = null, $icon = null)
    {
        $role = \App\Models\Role::where('role_name', $roleName)->first();

        if ($role) {
            $users = $role->users;

            foreach ($users as $user) {
                notifyUser($user->user_id, $type, $title, $message, $link, $icon);
            }
        }
    }
}
