<?php
/**
 * Fix Sales Order Approval Chain
 * Creates GM-only approval chain for sales_order module
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Setting up Sales Order Approval Chain</h2>";

// Check if chain already exists
$existing = $db->fetchOne(
    "SELECT * FROM approval_chains WHERE module = 'sales_order' AND step_order = 1"
);

if ($existing) {
    echo "<p style='color: green;'>✅ Approval chain for 'sales_order' already exists</p>";
    echo "<p>Step {$existing['step_order']}: {$existing['approver_role']} - {$existing['label']}</p>";
    echo "<p><strong>Approval chain is already set up correctly!</strong></p>";
    echo "<hr>";
    echo "<p><a href='debug_mark_as_paid_button.php'>→ Run diagnostic again</a></p>";
    exit;
}

// Create the approval chain step
echo "<p>Creating GM approval step for 'sales_order' module...</p>";

$db->query(
    "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step, created_at) 
     VALUES (?, ?, ?, ?, ?, NOW())",
    ['sales_order', 1, 'gm', 'General Manager Approval', 1]
);

$chain_id = $db->getConnection()->insert_id;

if (!$chain_id) {
    echo "<p style='color: red;'>❌ Failed to create approval chain</p>";
    exit;
}

echo "<p style='color: green;'>✅ Created GM approval step (ID: $chain_id)</p>";

echo "<hr>";
echo "<h3>✅ Sales Order Approval Chain Setup Complete!</h3>";

echo "<p><strong>What happens now:</strong></p>";
echo "<ol>";
echo "<li>When Sales creates a new order → Approval request is created automatically</li>";
echo "<li>GM sees the order in their pending approvals</li>";
echo "<li>GM approves → Order status changes to 'approved'</li>";
echo "<li>Sales can now see the 'Mark as Paid' button</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>⚠️ Important: Existing Orders</h3>";
echo "<p>Orders created BEFORE this fix won't have approval requests.</p>";
echo "<p>You have two options:</p>";
echo "<ol>";
echo "<li><strong>Create new test order</strong> - Recommended to verify the fix works</li>";
echo "<li><strong>Manually create approval requests</strong> - For existing orders (requires custom script)</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='debug_mark_as_paid_button.php'>Run diagnostic to verify setup</a></li>";
echo "<li>Create a new sales order as Sales user</li>";
echo "<li>Login as GM and approve the order</li>";
echo "<li>Login as Sales and verify 'Mark as Paid' button appears</li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Create Approval Requests for Existing Orders?</h3>";
echo "<p>If you want to fix existing orders that don't have approval requests, we can create them.</p>";

$orders_without_approval = $db->fetchAll(
    "SELECT so.id, so.so_number, so.status, so.created_by, so.created_at
     FROM sales_orders so
     LEFT JOIN approval_requests ar ON ar.reference_type='sales_order' AND ar.reference_id=so.id
     WHERE ar.id IS NULL
     ORDER BY so.created_at DESC"
);

if (empty($orders_without_approval)) {
    echo "<p style='color: green;'>✅ All sales orders have approval requests!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Found " . count($orders_without_approval) . " orders without approval requests:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>SO Number</th><th>Status</th><th>Created</th></tr>";
    foreach ($orders_without_approval as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['so_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "<td>" . date('M d, Y', strtotime($order['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    echo "<form method='POST' onsubmit='return confirm(\"Create approval requests for all existing orders?\");'>";
    echo "<input type='hidden' name='create_requests' value='1'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;'>Create Approval Requests for Existing Orders</button>";
    echo "</form>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_requests'])) {
    echo "<hr>";
    echo "<h3>Creating Approval Requests...</h3>";
    
    foreach ($orders_without_approval as $order) {
        // Create approval request
        $db->query(
            "INSERT INTO approval_requests (reference_type, reference_id, requested_by, current_step, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            ['sales_order', $order['id'], $order['created_by'], 1, 'pending', $order['created_at']]
        );
        
        $request_id = $db->getConnection()->insert_id;
        
        // Create approval step
        $db->query(
            "INSERT INTO approval_steps (request_id, step_order, role, label, status, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$request_id, 1, 'gm', 'General Manager Approval', 'pending']
        );
        
        echo "<p>✅ Created approval request for {$order['so_number']}</p>";
    }
    
    echo "<p style='color: green;'><strong>✅ All approval requests created!</strong></p>";
    echo "<p><a href='debug_mark_as_paid_button.php'>→ Run diagnostic to verify</a></p>";
}
