<?php
/**
 * WORKAROUND: Mark Sales Orders as Paid
 * Use this until the button deployment issue is resolved
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Mark Sales Orders as Paid</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
th { background: #f0f0f0; }
.btn { padding: 5px 10px; background: #28a745; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 3px; }
.btn:hover { background: #218838; }
</style>";

// Handle mark as paid action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $paymentDate = date('Y-m-d');
    
    try {
        // Get order details
        $order = $db->fetchOne(
            "SELECT so.*, ar.status as approval_status 
             FROM sales_orders so
             LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
             WHERE so.id=?",
            [$orderId], 'i'
        );
        
        if (!$order) {
            echo "<p class='error'>❌ Order not found</p>";
        } else {
            // Validate
            if ($order['status'] === 'rejected') {
                echo "<p class='error'>❌ Cannot mark as paid: Order is REJECTED</p>";
            } elseif ($order['status'] === 'cancelled') {
                echo "<p class='error'>❌ Cannot mark as paid: Order is CANCELLED</p>";
            } elseif ($order['approval_status'] === 'rejected') {
                echo "<p class='error'>❌ Cannot mark as paid: GM REJECTED this order</p>";
            } elseif ($order['approval_status'] === 'pending') {
                echo "<p class='error'>❌ Cannot mark as paid: Waiting for GM APPROVAL</p>";
            } elseif ($order['payment_status'] === 'paid') {
                echo "<p class='warning'>⚠️ Order is already marked as PAID</p>";
            } else {
                // Mark as paid
                $db->query(
                    "UPDATE sales_orders SET payment_status='paid', amount_paid=total_amount WHERE id=?",
                    [$orderId], 'i'
                );
                
                echo "<p class='success'>✅ Order {$order['so_number']} marked as PAID!</p>";
                echo "<p>Amount: ₱" . number_format($order['total_amount'], 2) . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

// Get unpaid orders
$orders = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status, so.payment_status, so.total_amount, so.amount_paid,
            c.name as customer_name,
            ar.id AS approval_request_id, 
            ar.status AS approval_status
     FROM sales_orders so
     JOIN customers c ON so.customer_id=c.id
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     WHERE so.payment_status != 'paid' OR so.payment_status IS NULL
     ORDER BY so.created_at DESC"
);

if (empty($orders)) {
    echo "<p class='success'>✅ All orders are paid!</p>";
    exit;
}

echo "<p>Found " . count($orders) . " unpaid orders:</p>";

echo "<table>";
echo "<tr>";
echo "<th>SO Number</th>";
echo "<th>Customer</th>";
echo "<th>Amount</th>";
echo "<th>Order Status</th>";
echo "<th>Payment Status</th>";
echo "<th>GM Approval</th>";
echo "<th>Can Mark Paid?</th>";
echo "<th>Action</th>";
echo "</tr>";

foreach ($orders as $o) {
    $paymentStatus = $o['payment_status'] ?? 'unpaid';
    $approvalStatus = $o['approval_status'] ?? 'none';
    $orderStatus = $o['status'];
    
    // Check if can be marked as paid
    $canMarkPaid = true;
    $reason = '';
    
    if ($orderStatus === 'rejected') {
        $canMarkPaid = false;
        $reason = 'Order rejected';
    } elseif ($orderStatus === 'cancelled') {
        $canMarkPaid = false;
        $reason = 'Order cancelled';
    } elseif ($approvalStatus === 'rejected') {
        $canMarkPaid = false;
        $reason = 'GM rejected';
    } elseif ($approvalStatus === 'pending') {
        $canMarkPaid = false;
        $reason = 'Awaiting GM approval';
    } elseif ($approvalStatus === 'none' || empty($o['approval_request_id'])) {
        $canMarkPaid = false;
        $reason = 'No approval request';
    } elseif ($paymentStatus === 'paid') {
        $canMarkPaid = false;
        $reason = 'Already paid';
    }
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($o['so_number']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($o['customer_name']) . "</td>";
    echo "<td>₱" . number_format($o['total_amount'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($orderStatus) . "</td>";
    echo "<td>" . htmlspecialchars($paymentStatus) . "</td>";
    echo "<td>" . htmlspecialchars($approvalStatus) . "</td>";
    echo "<td>" . ($canMarkPaid ? "<span class='success'>YES</span>" : "<span class='error'>NO</span><br><small>$reason</small>") . "</td>";
    echo "<td>";
    
    if ($canMarkPaid) {
        echo "<form method='POST' style='margin:0;' onsubmit='return confirm(\"Mark order {$o['so_number']} as PAID?\")'>";
        echo "<input type='hidden' name='order_id' value='{$o['id']}'>";
        echo "<button type='submit' class='btn'>Mark as Paid</button>";
        echo "</form>";
    } else {
        echo "<span style='color: #999;'>—</span>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Orders must be <strong>approved by GM</strong> before you can mark them as paid</li>";
echo "<li>Click <strong>'Mark as Paid'</strong> button next to any approved order</li>";
echo "<li>The order will be marked as paid with today's date</li>";
echo "<li>This is a temporary workaround until the button deployment is fixed</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='fix_sales_order_approval_chain.php'>→ Fix Missing Approval Requests</a></p>";
echo "<p><small>Last updated: " . date('Y-m-d H:i:s') . "</small></p>";
