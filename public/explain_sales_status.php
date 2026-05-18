<?php
/**
 * Sales Status Explanation
 * This script explains the difference between status and approval_status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Sales Order Status Explanation</h2>";

echo "<h3>Understanding the Two Status Fields:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Purpose</th><th>Possible Values</th><th>Who Controls It</th></tr>";

echo "<tr>";
echo "<td><strong>Delivery Status</strong><br>(sales_orders.status)</td>";
echo "<td>Tracks the order fulfillment workflow</td>";
echo "<td>
    • pending (just created)<br>
    • approved (ready to process)<br>
    • processing (being prepared)<br>
    • delivered (completed)<br>
    • cancelled<br>
    • rejected
</td>";
echo "<td>Sales Department</td>";
echo "</tr>";

echo "<tr>";
echo "<td><strong>GM Approval</strong><br>(approval_requests.status)</td>";
echo "<td>Tracks GM approval requirement</td>";
echo "<td>
    • pending (waiting for GM)<br>
    • approved (GM approved)<br>
    • rejected (GM rejected)
</td>";
echo "<td>General Manager</td>";
echo "</tr>";

echo "</table>";

echo "<h3>Current Sales Orders:</h3>";

$orders = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status AS delivery_status, 
            ar.status AS gm_approval_status,
            c.name AS customer_name,
            so.total_amount,
            so.payment_status
     FROM sales_orders so
     JOIN customers c ON so.customer_id = c.id
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     ORDER BY so.created_at DESC
     LIMIT 10"
);

if (empty($orders)) {
    echo "<p>No sales orders found.</p>";
} else {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>SO Number</th><th>Customer</th><th>Amount</th><th>Payment Status</th><th>Delivery Status</th><th>GM Approval</th>";
    echo "</tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['so_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . ($order['payment_status'] ?? 'unpaid') . "</td>";
        echo "<td><strong>" . $order['delivery_status'] . "</strong></td>";
        echo "<td><strong>" . ($order['gm_approval_status'] ?? 'N/A') . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<hr>";
echo "<h3>Typical Workflow:</h3>";
echo "<ol>";
echo "<li><strong>Sales creates order</strong> → Delivery Status: <code>pending</code>, GM Approval: <code>pending</code></li>";
echo "<li><strong>GM approves</strong> → Delivery Status: <code>approved</code>, GM Approval: <code>approved</code></li>";
echo "<li><strong>Sales marks as paid</strong> → Payment Status: <code>paid</code></li>";
echo "<li><strong>Sales updates delivery</strong> → Delivery Status: <code>processing</code> or <code>delivered</code></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Why Both Show 'Approved'?</h3>";
echo "<p>When GM approves an order:</p>";
echo "<ul>";
echo "<li>The <strong>GM Approval</strong> column changes from 'pending' to 'approved'</li>";
echo "<li>The <strong>Delivery Status</strong> column ALSO changes from 'pending' to 'approved'</li>";
echo "<li>This is intentional - 'approved' in Delivery Status means 'ready to fulfill'</li>";
echo "</ul>";

echo "<p><strong>Solution:</strong> The column headers have been renamed to:</p>";
echo "<ul>";
echo "<li>'Status' → '<strong>Delivery Status</strong>' (clearer purpose)</li>";
echo "<li>'Approval' → '<strong>GM Approval</strong>' (shows who approves)</li>";
echo "</ul>";
