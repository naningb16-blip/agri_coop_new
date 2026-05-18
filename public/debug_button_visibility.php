<?php
/**
 * Debug Mark as Paid Button Visibility
 * Shows exactly why the button is or isn't appearing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

session_start();

$db = Database::getInstance();

echo "<h2>Mark as Paid Button Visibility Debug</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
th { background: #f0f0f0; }
.code { background: #f5f5f5; padding: 2px 5px; font-family: monospace; }
</style>";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo "<p class='error'>❌ Not logged in. Please login first.</p>";
    echo "<p><a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/auth/login'>→ Go to Login</a></p>";
    exit;
}

$currentUser = $_SESSION['user'];
$userRole = $currentUser['role'] ?? 'unknown';

echo "<h3>Current User Info</h3>";
echo "<table style='width: auto;'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>User ID</td><td>{$currentUser['id']}</td></tr>";
echo "<tr><td>Username</td><td>" . htmlspecialchars($currentUser['username']) . "</td></tr>";
echo "<tr><td>Full Name</td><td>" . htmlspecialchars($currentUser['full_name']) . "</td></tr>";
echo "<tr><td>Role</td><td><strong>" . htmlspecialchars($userRole) . "</strong></td></tr>";
echo "</table>";

// Check role permission
$canSeeButton = in_array($userRole, ['admin', 'sales']);
echo "<p><strong>Can see button based on role?</strong> " . ($canSeeButton ? "<span class='success'>YES</span>" : "<span class='error'>NO (must be admin or sales)</span>") . "</p>";

if (!$canSeeButton) {
    echo "<p class='error'>❌ Your role is '<strong>$userRole</strong>' but the button only shows for 'admin' or 'sales' roles.</p>";
    echo "<p>You need to login as a user with 'admin' or 'sales' role to see the button.</p>";
    exit;
}

echo "<hr>";

// Get sales orders with all relevant data
echo "<h3>Sales Orders Analysis</h3>";
$orders = $db->fetchAll(
    "SELECT so.*, c.name AS customer_name,
            ar.id AS approval_request_id, 
            ar.status AS approval_status,
            ar.current_step,
            acs.label AS approval_step_label,
            acs.status as step_status
     FROM sales_orders so
     JOIN customers c ON so.customer_id=c.id
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     LEFT JOIN approval_steps acs ON acs.request_id=ar.id AND acs.step_order=ar.current_step
     ORDER BY so.created_at DESC
     LIMIT 20"
);

if (empty($orders)) {
    echo "<p class='warning'>⚠️ No sales orders found in database</p>";
    exit;
}

echo "<p>Found " . count($orders) . " orders. Analyzing button visibility...</p>";

echo "<table>";
echo "<tr>";
echo "<th>SO#</th>";
echo "<th>Customer</th>";
echo "<th>Order Status</th>";
echo "<th>Payment Status</th>";
echo "<th>Approval Request</th>";
echo "<th>Approval Status</th>";
echo "<th>Button Shows?</th>";
echo "<th>Why?</th>";
echo "</tr>";

foreach ($orders as $o) {
    $paymentStatus = $o['payment_status'] ?? 'unpaid';
    $approvalStatus = $o['approval_status'] ?? '';
    $orderStatus = $o['status'];
    $hasApprovalRequest = !empty($o['approval_request_id']);
    
    // Button logic from sales/index.php line 89
    $buttonShows = ($paymentStatus !== 'paid') && $canSeeButton;
    
    // Determine why button shows or doesn't show
    $reasons = [];
    if ($paymentStatus === 'paid') {
        $reasons[] = "❌ Already paid";
    } else {
        $reasons[] = "✅ Not paid yet";
    }
    
    if (!$canSeeButton) {
        $reasons[] = "❌ Wrong role";
    } else {
        $reasons[] = "✅ Correct role";
    }
    
    // JavaScript validation reasons (button shows but won't work)
    $jsBlocks = [];
    if ($orderStatus === 'rejected') {
        $jsBlocks[] = "JS blocks: Order rejected";
    }
    if ($orderStatus === 'cancelled') {
        $jsBlocks[] = "JS blocks: Order cancelled";
    }
    if ($orderStatus === 'pending') {
        $jsBlocks[] = "JS blocks: Order pending";
    }
    if ($approvalStatus === 'pending') {
        $jsBlocks[] = "JS blocks: Approval pending";
    }
    if ($approvalStatus === 'rejected') {
        $jsBlocks[] = "JS blocks: Approval rejected";
    }
    
    $whyText = implode("<br>", $reasons);
    if (!empty($jsBlocks)) {
        $whyText .= "<br><span class='warning'>" . implode("<br>", $jsBlocks) . "</span>";
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($o['so_number']) . "</td>";
    echo "<td>" . htmlspecialchars($o['customer_name']) . "</td>";
    echo "<td><span class='code'>" . htmlspecialchars($orderStatus) . "</span></td>";
    echo "<td><span class='code'>" . htmlspecialchars($paymentStatus) . "</span></td>";
    echo "<td>" . ($hasApprovalRequest ? "Yes (#{$o['approval_request_id']})" : "<span class='error'>NO</span>") . "</td>";
    echo "<td><span class='code'>" . htmlspecialchars($approvalStatus ?: 'none') . "</span></td>";
    echo "<td>" . ($buttonShows ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "</td>";
    echo "<td style='font-size: 11px;'>" . $whyText . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Button Logic Explanation</h3>";
echo "<p>The button appears when:</p>";
echo "<ol>";
echo "<li><code>payment_status !== 'paid'</code> (order is not already paid)</li>";
echo "<li><code>userRole in ['admin', 'sales']</code> (you are admin or sales user)</li>";
echo "</ol>";

echo "<p>The button is ALWAYS visible if these conditions are met, but JavaScript validation prevents it from working if:</p>";
echo "<ul>";
echo "<li>Order status is 'rejected', 'cancelled', or 'pending'</li>";
echo "<li>Approval status is 'pending' or 'rejected'</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>What to Check</h3>";
echo "<ol>";
echo "<li><strong>Hard refresh the page:</strong> Press Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)</li>";
echo "<li><strong>Check browser console:</strong> Press F12, go to Console tab, look for JavaScript errors</li>";
echo "<li><strong>Verify you're logged in as Sales or Admin:</strong> Your current role is <strong>$userRole</strong></li>";
echo "<li><strong>Check if orders have approval requests:</strong> Orders without approval requests won't work properly</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Quick Actions</h3>";
echo "<p><a href='check_mark_as_paid_status.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; display: inline-block; margin-right: 10px;'>Check Approval Chain Status</a></p>";
echo "<p><a href='fix_sales_order_approval_chain.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; display: inline-block;'>Fix Approval Chain</a></p>";

echo "<hr>";
echo "<p><small>Debug run at: " . date('Y-m-d H:i:s') . "</small></p>";
