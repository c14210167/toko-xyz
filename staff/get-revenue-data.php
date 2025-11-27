<?php
session_start();
require_once '../config/database.php';
require_once '../config/init_permissions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SESSION['user_type'] == 'customer') {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$period = $_GET['period'] ?? '30';
$type = $_GET['type'] ?? 'all';
$location_id = $_GET['location'] ?? 'all';

// Calculate date range
$end_date = date('Y-m-d');
if ($period === 'all') {
    $start_date = '2000-01-01'; // Far back enough for "all time"
} else {
    $days = (int)$period;
    $start_date = date('Y-m-d', strtotime("-$days days"));
}

// Determine grouping based on period
if ($period <= 7) {
    $date_format = '%Y-%m-%d';
    $date_label = 'DATE(created_at)';
    $php_format = 'D j';
} elseif ($period <= 30) {
    $date_format = '%Y-%m-%d';
    $date_label = 'DATE(created_at)';
    $php_format = 'M j';
} elseif ($period <= 90) {
    $date_format = '%Y-W%u';
    $date_label = 'YEARWEEK(created_at)';
    $php_format = 'W\eek W';
} else {
    $date_format = '%Y-%m';
    $date_label = 'DATE_FORMAT(created_at, "%Y-%m")';
    $php_format = 'M Y';
}

$labels = [];
$values = [];

// Build query based on type
if ($type === 'all' || $type === 'service') {
    // Get service revenue
    $service_query = "SELECT 
        DATE_FORMAT(o.created_at, '$date_format') as period,
        SUM(oc.total_cost) as revenue
    FROM orders o
    LEFT JOIN order_costs oc ON o.order_id = oc.order_id
    WHERE o.status = 'completed'
        AND DATE(o.created_at) BETWEEN :start_date AND :end_date";
    
    if ($location_id !== 'all') {
        $service_query .= " AND o.location_id = :location_id";
    }
    
    $service_query .= " GROUP BY period ORDER BY period";
    
    $service_stmt = $conn->prepare($service_query);
    $service_stmt->bindParam(':start_date', $start_date);
    $service_stmt->bindParam(':end_date', $end_date);
    if ($location_id !== 'all') {
        $service_stmt->bindParam(':location_id', $location_id);
    }
    $service_stmt->execute();
    
    while ($row = $service_stmt->fetch(PDO::FETCH_ASSOC)) {
        $period_key = $row['period'];
        if (!isset($values[$period_key])) {
            $values[$period_key] = 0;
        }
        $values[$period_key] += (float)$row['revenue'];
    }
}

if ($type === 'all' || $type === 'sales') {
    // Get product sales revenue
    $sales_query = "SELECT 
        DATE_FORMAT(s.created_at, '$date_format') as period,
        SUM(s.total_amount) as revenue
    FROM sales s";
    
    if ($location_id !== 'all') {
        $sales_query .= " WHERE s.location_id = :location_id AND";
    } else {
        $sales_query .= " WHERE";
    }
    
    $sales_query .= " DATE(s.created_at) BETWEEN :start_date AND :end_date
    GROUP BY period ORDER BY period";
    
    $sales_stmt = $conn->prepare($sales_query);
    $sales_stmt->bindParam(':start_date', $start_date);
    $sales_stmt->bindParam(':end_date', $end_date);
    if ($location_id !== 'all') {
        $sales_stmt->bindParam(':location_id', $location_id);
    }
    $sales_stmt->execute();
    
    while ($row = $sales_stmt->fetch(PDO::FETCH_ASSOC)) {
        $period_key = $row['period'];
        if (!isset($values[$period_key])) {
            $values[$period_key] = 0;
        }
        $values[$period_key] += (float)$row['revenue'];
    }
}

// Generate all periods for consistent labels
$current = strtotime($start_date);
$end = strtotime($end_date);

if ($period <= 30) {
    // Daily
    while ($current <= $end) {
        $period_key = date('Y-m-d', $current);
        $label = date($php_format, $current);
        $labels[] = $label;
        $values[] = isset($values[$period_key]) ? $values[$period_key] : 0;
        $current = strtotime('+1 day', $current);
    }
} elseif ($period <= 90) {
    // Weekly
    $weeks = [];
    while ($current <= $end) {
        $week_key = date('Y', $current) . '-W' . date('W', $current);
        if (!isset($weeks[$week_key])) {
            $weeks[$week_key] = [
                'label' => 'Week ' . date('W', $current),
                'value' => isset($values[$week_key]) ? $values[$week_key] : 0
            ];
        }
        $current = strtotime('+1 week', $current);
    }
    foreach ($weeks as $week) {
        $labels[] = $week['label'];
        $values[] = $week['value'];
    }
} else {
    // Monthly
    $months = [];
    while ($current <= $end) {
        $month_key = date('Y-m', $current);
        if (!isset($months[$month_key])) {
            $months[$month_key] = [
                'label' => date('M Y', $current),
                'value' => isset($values[$month_key]) ? $values[$month_key] : 0
            ];
        }
        $current = strtotime('+1 month', $current);
    }
    foreach ($months as $month) {
        $labels[] = $month['label'];
        $values[] = $month['value'];
    }
}

// If using aggregated data, convert back to array
if (is_array($values) && array_keys($values) !== range(0, count($values) - 1)) {
    // Already processed above, keep as is
}

echo json_encode([
    'labels' => $labels,
    'values' => array_values(is_array($values) ? (array_keys($values) === range(0, count($values) - 1) ? $values : array_values($values)) : [])
]);
?>