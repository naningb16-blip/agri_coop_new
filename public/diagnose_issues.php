<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h1>🔍 System Diagnostics</h1>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

// 1. Check approval chains
echo "<h2>1. Approval Chains Configuration</h2>";
$chains = $db->fetchAll("SELECT * FROM approval_chains ORDER BY module, step_order");
echo "<pre>";
foreach ($chains as $chain) {
    echo "Module: {$chain['module']}, Step: {$chain['step_order']}, Role: {$chain['approver_role']}\n";
}
echo "</pre>";

// 2. Check stock returns
echo "<h2>2. Stock Returns Status</h2>";
$returns = $db->fetchAll("SELECT * FROM stock_returns ORDER BY id DESC LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Has Approval?</th></tr>";
foreach ($returns as $r) {
    $approval = $db->fetchOne("SELECT id, status FROM approval_requests WHERE module='stock_return' AND reference_id=?", [$r['id']], 'i');
    $hasApproval = $approval ? "Yes (ID: {$approval['id']}, Status: {$approval['status']})" : "<span class='error'>NO</span>";
    echo "<tr><td>{$r['id']}</td><td>{$r['status']}</td><td>{$r['created_at']}</td><td>{$hasApproval}</td></tr>";
}
echo "</table>";

// 3. Check sales orders
echo "<h2>3. Sales Orders Status</h2>";
$orders = $db->fetchAll("SELECT * FROM sales_orders ORDER BY id DESC LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>SO #</th><th>Status</th><th>Payment</th><th>Created</th><th>Has Approval?</th></tr>";
foreach ($orders as $o) {
    $approval = $db->fetchOne("SELECT id, status FROM approval_requests WHERE module='sales_order' AND reference_id=?", [$o['id']], 'i');
    $hasApproval = $approval ? "Yes (ID: {$approval['id']}, Status: {$approval['status']})" : "<span class='error'>NO</span>";
    $paymentStatus = $o['payment_status'] ?? 'unpaid';
    $soNumber = $o['so_number'] ?? $o['order_number'] ?? 'N/A';
    echo "<tr><td>{$o['id']}</td><td>{$soNumber}</td><td>{$o['status']}</td><td>{$paymentStatus}</td><td>{$o['created_at']}</td><td>{$hasApproval}</td></tr>";
}
echo "</table>";

// 4. Check approval requests
echo "<h2>4. Recent Approval Requests</h2>";
$approvals = $db->fetchAll("SELECT * FROM approval_requests ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Module</th><th>Ref ID</th><th>Status</th><th>Current Step</th><th>Created</th></tr>";
foreach ($approvals as $a) {
    echo "<tr><td>{$a['id']}</td><td>{$a['module']}</td><td>{$a['reference_id']}</td><td>{$a['status']}</td><td>{$a['current_step']}</td><td>{$a['created_at']}</td></tr>";
}
echo "</table>";

// 5. Check approval steps for pending requests
echo "<h2>5. Approval Steps for Pending Requests</h2>";
$pendingApprovals = $db->fetchAll("SELECT id, module, reference_id FROM approval_requests WHERE status='pending' LIMIT 5");
foreach ($pendingApprovals as $pa) {
    echo "<h3>Request #{$pa['id']} ({$pa['module']} - Ref: {$pa['reference_id']})</h3>";
    $steps = $db->fetchAll("SELECT * FROM approval_steps WHERE request_id=? ORDER BY step_order", [$pa['id']], 'i');
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Step</th><th>Role</th><th>Status</th><th>Acted By</th><th>Acted At</th></tr>";
    foreach ($steps as $s) {
        echo "<tr><td>{$s['step_order']}</td><td>{$s['approver_role']}</td><td>{$s['status']}</td><td>{$s['acted_by']}</td><td>{$s['acted_at']}</td></tr>";
    }
    echo "</table>";
}

// 6. Check sales_orders table structure
echo "<h2>6. Sales Orders Table Structure</h2>";
$columns = $db->fetchAll("SHOW COLUMNS FROM sales_orders");
echo "<pre>";
foreach ($columns as $col) {
    echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']}\n";
}
echo "</pre>";

// 7. Check if payment_status column exists
echo "<h2>7. Payment Status Column Check</h2>";
$hasPaymentStatus = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'payment_status') {
        $hasPaymentStatus = true;
        break;
    }
}
echo $hasPaymentStatus ? "<span class='ok'>✅ payment_status column EXISTS</span>" : "<span class='error'>❌ payment_status column MISSING</span>";

// 8. Check stock_returns table structure
echo "<h2>8. Stock Returns Table Structure</h2>";
$returnColumns = $db->fetchAll("SHOW COLUMNS FROM stock_returns");
echo "<pre>";
foreach ($returnColumns as $col) {
    echo "{$col['Field']} - {$col['Type']}\n";
}
echo "</pre>";

// 9. Test query for mark as paid button visibility
echo "<h2>9. Mark as Paid Button Test</h2>";
echo "<p>Testing conditions for button visibility...</p>";
$testOrder = $db->fetchOne("SELECT * FROM sales_orders WHERE status='approved' LIMIT 1");
if ($testOrder) {
    echo "<pre>";
    echo "Order ID: {$testOrder['id']}\n";
    $soNumber = $testOrder['so_number'] ?? $testOrder['order_number'] ?? 'N/A';
    echo "SO Number: {$soNumber}\n";
    echo "Status: {$testOrder['status']}\n";
    echo "Payment Status: " . ($testOrder['payment_status'] ?? 'NOT SET') . "\n";
    
    $approval = $db->fetchOne("SELECT status FROM approval_requests WHERE module='sales_order' AND reference_id=?", [$testOrder['id']], 'i');
    echo "Approval Status: " . ($approval ? $approval['status'] : 'NO APPROVAL') . "\n";
    
    $shouldShow = ($testOrder['payment_status'] ?? 'unpaid') !== 'paid';
    echo "\nButton should show: " . ($shouldShow ? "<span class='ok'>YES</span>" : "<span class='error'>NO</span>") . "\n";
    echo "</pre>";
} else {
    echo "<span class='warning'>No approved orders found</span>";
}

// 10. Check GM user
echo "<h2>10. GM User Check</h2>";
$gm = $db->fetchOne("SELECT id, username, full_name, role FROM users WHERE role='gm'");
if ($gm) {
    echo "<span class='ok'>✅ GM user exists: {$gm['full_name']} (ID: {$gm['id']})</span>";
} else {
    echo "<span class='error'>❌ No GM user found</span>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li>Approval chains configured: " . count($chains) . "</li>";
echo "<li>Stock returns: " . count($returns) . "</li>";
echo "<li>Sales orders: " . count($orders) . "</li>";
echo "<li>Pending approvals: " . count($pendingApprovals) . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='/'>← Back to Dashboard</a></p>";
