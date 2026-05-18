<?php
/**
 * Simple Sales Order Check - No Session Required
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Sales Orders & Button Visibility Check</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
th { background: #f0f0f0; }
.code { background: #f5f5f5; padding: 2px 5px; font-family: monospace; }
</style>";

// 1. Check approval chain
echo "<h3>1. Approval Chain Status</h3>";
$chain = $db->fetchOne(
    "SELECT * FROM approval_chains WHERE module = 'sales_order' AND step_order = 1"
);

if ($chain) {
    echo "<p class='success'>✅ Approval chain EXISTS</p>";
} else {
    echo "<p class='error'>❌ NO approval chain found!</p>";
    echo "<p><strong>This is the problem!</strong> Without an approval chain, orders won't have approval_status.</p>";
    echo "<p><a href='fix_sales_order_approval_chain.php' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; display: inline-block;'>→ FIX THIS NOW</a></p>";
}

// 2. Get all sales orders
echo "<hr><h3>2. Sales Orders Analysis</h3>";
$orders = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status, so.payment_status, so.amount_paid, so.total_amount,
            c.name as customer_name,
            ar.id AS approval_request_id, 
            ar.status AS approval_status
     FROM sales_orders so
     JOIN customers c ON so.customer_id=c.id
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     ORDER BY so.created_at DESC
     LIMIT 20"
);

if (empty($orders)) {
    echo "<p class='warning'>⚠️ No sales orders found</p>";
    exit;
}

echo "<p>Found " . count($orders) . " orders</p>";

echo "<table>";
echo "<tr>";
echo "<th>SO Number</th>";
echo "<th>Customer</th>";
echo "<th>Total</th>";
echo "<th>Paid</th>";
echo "<th>Order Status</th>";
echo "<th>Payment Status</th>";
echo "<th>Has Approval?</th>";
echo "<th>Approval Status</th>";
echo "<th>Button Should Show?</th>";
echo "</tr>";

$ordersWithButton = 0;
$ordersWithoutApproval = 0;

foreach ($orders as $o) {
    $paymentStatus = $o['payment_status'] ?? 'unpaid';
    $approvalStatus = $o['approval_status'] ?? null;
    $hasApproval = !empty($o['approval_request_id']);
    
    // Button logic: shows if payment_status != 'paid' AND user is admin/sales
    // We'll assume user is admin/sales for this check
    $buttonShows = ($paymentStatus !== 'paid');
    
    if ($buttonShows) $ordersWithButton++;
    if (!$hasApproval) $ordersWithoutApproval++;
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($o['so_number']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($o['customer_name']) . "</td>";
    echo "<td>₱" . number_format($o['total_amount'], 2) . "</td>";
    echo "<td>₱" . number_format($o['amount_paid'] ?? 0, 2) . "</td>";
    echo "<td><span class='code'>" . htmlspecialchars($o['status']) . "</span></td>";
    echo "<td><span class='code'>" . htmlspecialchars($paymentStatus) . "</span></td>";
    echo "<td>" . ($hasApproval ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</td>";
    echo "<td><span class='code'>" . htmlspecialchars($approvalStatus ?: 'none') . "</span></td>";
    echo "<td>" . ($buttonShows ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

// 3. Summary
echo "<hr><h3>3. Summary</h3>";
echo "<table style='width: auto;'>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
echo "<tr><td>Total Orders</td><td>" . count($orders) . "</td></tr>";
echo "<tr><td>Orders that should show button</td><td class='success'>" . $ordersWithButton . "</td></tr>";
echo "<tr><td>Orders WITHOUT approval requests</td><td class='" . ($ordersWithoutApproval > 0 ? 'error' : 'success') . "'>" . $ordersWithoutApproval . "</td></tr>";
echo "<tr><td>Approval chain exists?</td><td class='" . ($chain ? 'success' : 'error') . "'>" . ($chain ? 'YES' : 'NO') . "</td></tr>";
echo "</table>";

// 4. Diagnosis
echo "<hr><h3>4. Diagnosis</h3>";

if (!$chain) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 10px 0;'>";
    echo "<h4 style='margin-top: 0; color: #856404;'>⚠️ CRITICAL: No Approval Chain</h4>";
    echo "<p>The approval chain for 'sales_order' doesn't exist. This means:</p>";
    echo "<ul>";
    echo "<li>New orders won't get approval requests automatically</li>";
    echo "<li>Orders won't have approval_status</li>";
    echo "<li>The system can't track GM approvals</li>";
    echo "</ul>";
    echo "<p><strong>Action:</strong> <a href='fix_sales_order_approval_chain.php'>Run the fix script</a></p>";
    echo "</div>";
}

if ($ordersWithoutApproval > 0) {
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 10px 0;'>";
    echo "<h4 style='margin-top: 0; color: #856404;'>⚠️ WARNING: Orders Missing Approval Requests</h4>";
    echo "<p>Found {$ordersWithoutApproval} orders without approval requests. These orders:</p>";
    echo "<ul>";
    echo "<li>Were created before the approval chain existed</li>";
    echo "<li>Don't have approval_status in the database</li>";
    echo "<li>Won't work properly with the approval system</li>";
    echo "</ul>";
    echo "<p><strong>Action:</strong> <a href='fix_sales_order_approval_chain.php'>Run the fix script and create approval requests</a></p>";
    echo "</div>";
}

if ($chain && $ordersWithoutApproval === 0 && $ordersWithButton > 0) {
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 15px; margin: 10px 0;'>";
    echo "<h4 style='margin-top: 0; color: #155724;'>✅ Everything Looks Good!</h4>";
    echo "<p>The button should be visible for {$ordersWithButton} unpaid orders.</p>";
    echo "<p><strong>If you still don't see the button:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure you're logged in as <strong>admin</strong> or <strong>sales</strong> user</li>";
    echo "<li>Hard refresh the page: <strong>Ctrl+Shift+R</strong> (Windows) or <strong>Cmd+Shift+R</strong> (Mac)</li>";
    echo "<li>Clear your browser cache completely</li>";
    echo "<li>Check browser console (F12) for JavaScript errors</li>";
    echo "<li>Try a different browser or incognito mode</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>5. Next Steps</h3>";

if (!$chain || $ordersWithoutApproval > 0) {
    echo "<p><a href='fix_sales_order_approval_chain.php' style='padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; display: inline-block; font-size: 16px; border-radius: 5px;'>→ FIX APPROVAL CHAIN NOW</a></p>";
} else {
    echo "<p>Database looks good. The issue is likely:</p>";
    echo "<ul>";
    echo "<li><strong>Browser cache:</strong> Old version of the page is cached</li>";
    echo "<li><strong>User role:</strong> You're not logged in as admin or sales</li>";
    echo "<li><strong>Session issue:</strong> Your session doesn't have the right role</li>";
    echo "</ul>";
    
    echo "<h4>To check your current role:</h4>";
    echo "<ol>";
    echo "<li>Go to your sales page</li>";
    echo "<li>Press F12 to open browser console</li>";
    echo "<li>Check the page source (Ctrl+U) and search for 'userRole'</li>";
    echo "<li>The button only shows for users with 'admin' or 'sales' role</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><small>Checked at: " . date('Y-m-d H:i:s') . "</small></p>";
