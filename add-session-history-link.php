<?php
/**
 * Script to add Session History link to all sidebar files
 */

$files = [
    'staff/dashboard.php',
    'staff/orders.php',
    'staff/customers.php',
    'staff/inventory.php',
    'staff/inventory-taking.php',
    'staff/suppliers.php',
    'staff/employees.php',
    'staff/roles.php',
    'staff/locations.php',
    'staff/activities.php'
];

$updated = 0;
$skipped = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️  Skipping $file (not found)\n";
        $skipped++;
        continue;
    }

    $content = file_get_contents($file);

    // Check if already has Session History link
    if (strpos($content, 'session-history.php') !== false) {
        echo "ℹ️  Skipping $file (already has Session History link)\n";
        $skipped++;
        continue;
    }

    // Use regex to match with flexible whitespace
    $pattern = '/(.*<a href="pos\.php" class="nav-item">.*?<span class="nav-text">Point of Sale<\/span>.*?<\/a>)(.*?<a href="orders\.php" class="nav-item">)/s';

    $replacement = '$1
            <a href="session-history.php" class="nav-item">
                <span class="nav-icon">📜</span>
                <span class="nav-text">Session History</span>
            </a>$2';

    $new_content = preg_replace($pattern, $replacement, $content);

    if ($new_content !== null && $new_content !== $content) {
        file_put_contents($file, $new_content);
        echo "✅ Updated $file\n";
        $updated++;
    } else {
        echo "⚠️  Pattern not found in $file\n";
        $skipped++;
    }
}

echo "\n📊 Summary: $updated files updated, $skipped files skipped\n";
?>
