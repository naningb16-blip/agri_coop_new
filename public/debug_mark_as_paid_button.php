<?php
/**
 * Debug Mark as Paid Button
 * This script shows why the "Mark as Paid" button is not appearing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Debug: Mark as Paid Button</h2>";

// Get all sales orders with their approval status
$orders = $db->fetchAll(
    "SELECT so.id, so.so_number, 
            so.status AS delivery_status,
            so.payment_status,
            so.payment_type,
            ar.id AS approval_request_id,
            ar.status AS approval_status,
            ar.current_step,
            acs.label AS approval_step_label
     FROM sales_orders so
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     LEFT JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step
     ORDER BY so.created_at DESC
     LIMIT 20"
);

if (empty($orders)) {
    echo "<p style='color: red;'>❌ No sales orders found in database.</p>";
    exit;
}

echo "<p>Checking conditions for 'Mark as Paid' button to appear...</p>";
echo "<p><strong>Button appears when ALL these are true:</strong></p>";
echo "<ol>";
echo "<li>Payment Status ≠ 'paid'</li>";
echo "<li>Approval Status = 'approved'</li>";
echo "<li>User Role = 'admin' or 'sales' (not 'gm')</li>";
echo "</ol>";

echo "<hr>";

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>SO Number</th>";
echo "<th>Delivery Status</th>";
echo "<th>Payment Status</th>";
echo "<th>Approval Request ID</th>";
echo "<th>Approval Status</th>";
echo "<th>Current Step</th>";
echo "<th>Button Shows?</th>";
echo "<th>Why/Why Not?</th>";
echo "</tr>";

foreach ($orders as $order) {
    $payment_status = $order['payment_status'] ?? 'unpaid';
    $approval_status = $order['approval_status'] ?? null;
    $approval_request_id = $order['approval_request_id'];
    
    // Check conditions
    $condition1 = $payment_status !== 'paid';
    $condition2 = $approval_status === 'approved';
    
    $button_shows = $condition1 && $condition2;
    
    // Determine reason
    $reasons = [];
    if (!$condition1) {
        $reasons[] = "Already paid";
    }
    if (!$approval_request_id) {
        $reasons[] = "No approval request found";
    }
    if ($approval_status === null) {
        $reasons[] = "Approval status is NULL";
    }
    if ($approval_status === 'pending') {
        $reasons[] = "Still waiting for GM approval";
    }
    if ($approval_status === 'rejected') {
        $reasons[] = "Order was rejected";
    }
    if ($button_shows) {
        $reasons[] = "✅ All conditions met!";
    }
    
    $row_color = $button_shows ? '#d4edda' : '#f8d7da';
    
    echo "<tr style='background: $row_color;'>";
    echo "<td><strong>" . htmlspecialchars($order['so_number']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($order['delivery_status']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($payment_status) . "</strong></td>";
    echo "<td>" . ($approval_request_id ? $approval_request_id : '<span style="color: red;">NULL</span>') . "</td>";
    echo "<td><strong>" . ($approval_status ? htmlspecialchars($approval_status) : '<span style="color: red;">NULL</span>') . "</strong></td>";
    echo "<td>" . ($order['approval_step_label'] ?? 'N/A') . "</td>";
    echo "<td style='text-align: center; font-size: 20px;'>" . ($button_shows ? '✅' : '❌') . "</td>";
    echo "<td>" . implode('<br>', $reasons) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Common Issues:</h3>";
echo "<ol>";
echo "<li><strong>No approval request found (NULL)</strong> - The sales order was created but no approval request was generated. This happens if the approval chain is not set up.</li>";
echo "<li><strong>Approval status is NULL</strong> - The approval_requests table has no record for this order.</li>";
echo "<li><strong>Approval status is 'pending'</strong> - GM hasn't approved it yet.</li>";
echo "<li><strong>Already paid</strong> - Payment status is already 'paid', so button is hidden.</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Check Approval Chain Setup:</h3>";

$chain = $db->fetchOne(
    "SELECT * FROM approval_chains 
     WHERE module = 'sales_order' 
     LIMIT 1"
);

if ($chain) {
    echo "<p style='color: green;'>✅ Approval chain for 'sales_order' exists (ID: {$chain['id']})</p>";
    echo "<p>Step {$chain['step_order']}: {$chain['approver_role']} - {$chain['label']}</p>";
} else {
    echo "<p style='color: red;'>❌ No approval chain found for 'sales_order' module!</p>";
    echo "<p><strong>Solution:</strong> Run <a href='fix_sales_order_approval_chain.php'>fix_sales_order_approval_chain.php</a> to create it.</p>";
}

echo "<hr>";
echo "<h3>Your Current User Role:</h3>";
echo "<p>The button only shows for <strong>admin</strong> or <strong>sales</strong> roles, NOT for <strong>gm</strong>.</p>";
echo "<p>Make sure you're logged in as Sales or Admin user when testing.</p>";
