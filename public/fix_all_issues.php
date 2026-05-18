<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h1>🔧 Fixing All Issues</h1>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Fix 1: Link existing sales orders to approval system
echo "<h2>1. Linking Sales Orders to Approval System</h2>";

// Get all sales orders without approval requests
$ordersWithoutApproval = $db->fetchAll("
    SELECT so.id, so.so_number, so.status 
    FROM sales_orders so
    LEFT JOIN approval_requests ar ON ar.module='sales_order' AND ar.reference_id=so.id
    WHERE ar.id IS NULL
    ORDER BY so.id
");

echo "<p>Found " . count($ordersWithoutApproval) . " sales orders without approval requests</p>";

$created = 0;
foreach ($ordersWithoutApproval as $order) {
    // Create approval request
    $requestId = $db->insert(
        "INSERT INTO approval_requests (module, reference_type, reference_id, title, description, requested_by, status, current_step, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            'sales_order',
            'sales_order',
            $order['id'],
            "Sales Order {$order['so_number']}",
            "Approval request for sales order {$order['so_number']}",
            1, // System user
            $order['status'] === 'approved' || $order['status'] === 'delivered' ? 'approved' : 'pending',
            1
        ],
        'ssissssi'
    );
    
    // Create approval step
    $stepStatus = ($order['status'] === 'approved' || $order['status'] === 'delivered') ? 'approved' : 'pending';
    $db->insert(
        "INSERT INTO approval_steps (request_id, step_order, approver_role, status, acted_by, acted_at) 
         VALUES (?, ?, ?, ?, ?, ?)",
        [
            $requestId,
            1,
            'gm',
            $stepStatus,
            $stepStatus === 'approved' ? 1 : null,
            $stepStatus === 'approved' ? date('Y-m-d H:i:s') : null
        ],
        'iissis'
    );
    
    $created++;
    echo "<span class='ok'>✅ Created approval for order #{$order['id']} ({$order['so_number']}) - Status: {$stepStatus}</span><br>";
}

echo "<p><strong>Created {$created} approval requests for sales orders</strong></p>";

// Fix 2: Ensure stock return approval steps are correct
echo "<h2>2. Verifying Stock Return Approvals</h2>";

$stockReturns = $db->fetchAll("
    SELECT sr.id, sr.status, ar.id as approval_id, ar.status as approval_status
    FROM stock_returns sr
    LEFT JOIN approval_requests ar ON ar.module='stock_return' AND ar.reference_id=sr.id
    WHERE sr.status='pending'
");

echo "<p>Found " . count($stockReturns) . " pending stock returns</p>";

foreach ($stockReturns as $return) {
    if ($return['approval_id']) {
        // Check if approval steps exist
        $steps = $db->fetchAll("SELECT * FROM approval_steps WHERE request_id=?", [$return['approval_id']], 'i');
        if (empty($steps)) {
            // Create missing step
            $db->insert(
                "INSERT INTO approval_steps (request_id, step_order, approver_role, status) 
                 VALUES (?, ?, ?, ?)",
                [$return['approval_id'], 1, 'gm', 'pending'],
                'iiss'
            );
            echo "<span class='ok'>✅ Created approval step for stock return #{$return['id']}</span><br>";
        } else {
            echo "<span class='ok'>✅ Stock return #{$return['id']} has approval steps</span><br>";
        }
    } else {
        // Create approval request
        $requestId = $db->insert(
            "INSERT INTO approval_requests (module, reference_type, reference_id, title, description, requested_by, status, current_step, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                'stock_return',
                'stock_return',
                $return['id'],
                "Stock Return #{$return['id']}",
                "Approval request for stock return",
                1,
                'pending',
                1
            ],
            'ssissssi'
        );
        
        // Create approval step
        $db->insert(
            "INSERT INTO approval_steps (request_id, step_order, approver_role, status) 
             VALUES (?, ?, ?, ?)",
            [$requestId, 1, 'gm', 'pending'],
            'iiss'
        );
        
        echo "<span class='ok'>✅ Created approval for stock return #{$return['id']}</span><br>";
    }
}

// Fix 3: Verify approval chains
echo "<h2>3. Verifying Approval Chains</h2>";

$chains = ['sales_order' => 'gm', 'stock_return' => 'gm'];
foreach ($chains as $module => $role) {
    $existing = $db->fetchOne("SELECT * FROM approval_chains WHERE module=? AND step_order=1", [$module], 's');
    if ($existing) {
        echo "<span class='ok'>✅ Approval chain for {$module} exists (Role: {$existing['approver_role']})</span><br>";
    } else {
        $db->insert(
            "INSERT INTO approval_chains (module, step_order, approver_role, step_label) VALUES (?, ?, ?, ?)",
            [$module, 1, $role, 'GM Approval'],
            'siss'
        );
        echo "<span class='ok'>✅ Created approval chain for {$module}</span><br>";
    }
}

echo "<hr>";
echo "<h2>✅ All Fixes Applied!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Clear your browser cache (Ctrl+Shift+Delete)</li>";
echo "<li>Login as <strong>sales</strong> or <strong>admin</strong> user</li>";
echo "<li>Go to Sales module - you should see <strong>Mark as Paid</strong> buttons</li>";
echo "<li>Login as <strong>GM</strong> user</li>";
echo "<li>Go to Approvals - you should see <strong>Approve/Reject</strong> buttons for stock returns</li>";
echo "</ol>";

echo "<p><a href='/diagnose_issues.php'>← Run Diagnostic Again</a> | <a href='/'>Go to Dashboard</a></p>";
