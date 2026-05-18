<?php
/**
 * Quick diagnostic for Mark as Paid button issue
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Mark as Paid Button Diagnostic</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style>";

// 1. Check if approval chain exists
echo "<h3>1. Approval Chain Check</h3>";
$chain = $db->fetchOne(
    "SELECT * FROM approval_chains WHERE module = 'sales_order' AND step_order = 1"
);

if ($chain) {
    echo "<p class='success'>✅ Approval chain exists for 'sales_order'</p>";
    echo "<p>Step: {$chain['step_order']} | Role: {$chain['approver_role']} | Label: {$chain['label']}</p>";
} else {
    echo "<p class='error'>❌ NO approval chain found for 'sales_order'</p>";
    echo "<p><strong>ACTION REQUIRED:</strong> Run <a href='fix_sales_order_approval_chain.php'>fix_sales_order_approval_chain.php</a></p>";
}

// 2. Check sales orders
echo "<hr><h3>2. Sales Orders Status</h3>";
$orders = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status, so.payment_status, so.created_at,
            ar.id as approval_request_id, ar.status as approval_status
     FROM sales_orders so
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     ORDER BY so.created_at DESC
     LIMIT 10"
);

if (empty($orders)) {
    echo "<p class='warning'>⚠️ No sales orders found</p>";
} else {
    echo "<table>";
    echo "<tr><th>SO Number</th><th>Order Status</th><th>Payment Status</th><th>Approval Request</th><th>Approval Status</th><th>Button Should Show?</th></tr>";
    
    foreach ($orders as $order) {
        $hasApproval = !empty($order['approval_request_id']);
        $approvalStatus = $order['approval_status'] ?? 'none';
        $orderStatus = $order['status'];
        $paymentStatus = $order['payment_status'] ?? 'unpaid';
        
        // Button logic from sales/index.php
        $shouldShow = ($paymentStatus !== 'paid') && ($approvalStatus === 'approved');
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['so_number']) . "</td>";
        echo "<td>" . htmlspecialchars($orderStatus) . "</td>";
        echo "<td>" . htmlspecialchars($paymentStatus) . "</td>";
        echo "<td>" . ($hasApproval ? "Yes (#{$order['approval_request_id']})" : "<span class='error'>NO</span>") . "</td>";
        echo "<td>" . htmlspecialchars($approvalStatus) . "</td>";
        echo "<td>" . ($shouldShow ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// 3. Count orders without approval requests
echo "<hr><h3>3. Orders Without Approval Requests</h3>";
$missing = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status, so.created_at
     FROM sales_orders so
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     WHERE ar.id IS NULL"
);

if (empty($missing)) {
    echo "<p class='success'>✅ All sales orders have approval requests</p>";
} else {
    echo "<p class='warning'>⚠️ Found " . count($missing) . " orders without approval requests</p>";
    echo "<p><strong>ACTION REQUIRED:</strong> Run <a href='fix_sales_order_approval_chain.php'>fix_sales_order_approval_chain.php</a> and use the form to create approval requests</p>";
}

// 4. Summary
echo "<hr><h3>Summary</h3>";
echo "<ol>";
echo "<li><strong>Approval Chain:</strong> " . ($chain ? "✅ Exists" : "❌ Missing") . "</li>";
echo "<li><strong>Orders with Approvals:</strong> " . (count($orders) - count($missing)) . " / " . count($orders) . "</li>";
echo "<li><strong>Orders Missing Approvals:</strong> " . count($missing) . "</li>";
echo "</ol>";

if (!$chain) {
    echo "<p class='error'><strong>CRITICAL:</strong> No approval chain exists. The button will NEVER show until you create it.</p>";
    echo "<p><a href='fix_sales_order_approval_chain.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; display: inline-block;'>→ Fix Approval Chain Now</a></p>";
} elseif (!empty($missing)) {
    echo "<p class='warning'><strong>WARNING:</strong> Some orders don't have approval requests. They won't show the button.</p>";
    echo "<p><a href='fix_sales_order_approval_chain.php' style='padding: 10px 20px; background: #ffc107; color: black; text-decoration: none; display: inline-block;'>→ Create Missing Approval Requests</a></p>";
} else {
    echo "<p class='success'><strong>✅ Everything looks good!</strong> The button should appear for approved, unpaid orders.</p>";
    echo "<p>If you still don't see the button, try:</p>";
    echo "<ol>";
    echo "<li>Hard refresh the page (Ctrl+Shift+R or Cmd+Shift+R)</li>";
    echo "<li>Clear browser cache</li>";
    echo "<li>Check browser console for JavaScript errors (F12)</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><small>Last checked: " . date('Y-m-d H:i:s') . "</small></p>";
