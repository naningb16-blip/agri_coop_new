<?php
// Diagnostic script for stock return approval system
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Stock Return Approval Diagnostic</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";

// 1. Check approval chain
echo "<h3>1. Approval Chain for Stock Returns</h3>";
$chain = $db->fetchAll(
    "SELECT * FROM approval_chains WHERE module='stock_return' ORDER BY step_order"
);

if (empty($chain)) {
    echo "<p class='error'>❌ No approval chain found for stock_return module</p>";
    echo "<p>Run <code>fix_stock_return_gm_approval.php</code> to create it.</p>";
} else {
    echo "<p class='success'>✅ Approval chain exists</p>";
    echo "<table>";
    echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Is GM Step</th></tr>";
    foreach ($chain as $c) {
        echo "<tr>";
        echo "<td>{$c['step_order']}</td>";
        echo "<td>{$c['approver_role']}</td>";
        echo "<td>{$c['label']}</td>";
        echo "<td>" . ($c['is_gm_step'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Check recent stock returns
echo "<h3>2. Recent Stock Returns</h3>";
$returns = $db->fetchAll(
    "SELECT sr.*, p.name as product_name, w.name as warehouse_name,
            u.full_name as created_by_name,
            ar.id as approval_request_id, ar.status as approval_status
     FROM stock_returns sr
     JOIN products p ON sr.product_id = p.id
     JOIN warehouses w ON sr.warehouse_id = w.id
     JOIN users u ON sr.created_by = u.id
     LEFT JOIN approval_requests ar ON ar.reference_type='stock_return' AND ar.reference_id=sr.id
     ORDER BY sr.created_at DESC
     LIMIT 10"
);

if (empty($returns)) {
    echo "<p class='warning'>No stock returns found</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Warehouse</th><th>Qty</th><th>Condition</th><th>Status</th><th>Approval ID</th><th>Approval Status</th></tr>";
    foreach ($returns as $r) {
        $conditionClass = ['good'=>'success','damaged'=>'error','expired'=>'warning'];
        $statusClass = ['pending'=>'warning','restocked'=>'success','disposed'=>'error','rejected'=>'error'];
        
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['product_name']}</td>";
        echo "<td>{$r['warehouse_name']}</td>";
        echo "<td>{$r['quantity']}</td>";
        echo "<td class='{$conditionClass[$r['condition_type']]}'>{$r['condition_type']}</td>";
        echo "<td class='{$statusClass[$r['status']]}'>{$r['status']}</td>";
        echo "<td>" . ($r['approval_request_id'] ?: 'N/A') . "</td>";
        echo "<td>" . ($r['approval_status'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Check for orphaned returns (no approval request)
echo "<h3>3. Orphaned Returns (No Approval Request)</h3>";
$orphaned = $db->fetchAll(
    "SELECT sr.*, p.name as product_name
     FROM stock_returns sr
     JOIN products p ON sr.product_id = p.id
     LEFT JOIN approval_requests ar ON ar.reference_type='stock_return' AND ar.reference_id=sr.id
     WHERE ar.id IS NULL AND sr.status='pending'"
);

if (empty($orphaned)) {
    echo "<p class='success'>✅ No orphaned returns found</p>";
} else {
    echo "<p class='error'>❌ Found " . count($orphaned) . " returns without approval requests:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Quantity</th><th>Condition</th><th>Created</th></tr>";
    foreach ($orphaned as $o) {
        echo "<tr>";
        echo "<td>{$o['id']}</td>";
        echo "<td>{$o['product_name']}</td>";
        echo "<td>{$o['quantity']}</td>";
        echo "<td>{$o['condition_type']}</td>";
        echo "<td>{$o['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>These returns need approval requests created manually.</p>";
}

// 4. Check stock movements from returns
echo "<h3>4. Stock Movements from Returns</h3>";
$movements = $db->fetchAll(
    "SELECT sm.*, p.name as product_name, w.name as warehouse_name
     FROM stock_movements sm
     JOIN products p ON sm.product_id = p.id
     JOIN warehouses w ON sm.warehouse_id = w.id
     WHERE sm.reference_type='stock_return'
     ORDER BY sm.created_at DESC
     LIMIT 10"
);

if (empty($movements)) {
    echo "<p class='warning'>No stock movements from returns yet</p>";
} else {
    echo "<p class='success'>✅ Found " . count($movements) . " stock movements from returns</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Warehouse</th><th>Type</th><th>Quantity</th><th>Notes</th><th>Date</th></tr>";
    foreach ($movements as $m) {
        echo "<tr>";
        echo "<td>{$m['id']}</td>";
        echo "<td>{$m['product_name']}</td>";
        echo "<td>{$m['warehouse_name']}</td>";
        echo "<td>{$m['type']}</td>";
        echo "<td>{$m['quantity']}</td>";
        echo "<td>{$m['notes']}</td>";
        echo "<td>{$m['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
if (!empty($chain)) {
    echo "<p class='success'>✅ Approval chain is configured</p>";
} else {
    echo "<p class='error'>❌ Approval chain is missing - run fix script</p>";
}

if (empty($orphaned)) {
    echo "<p class='success'>✅ All returns have approval requests</p>";
} else {
    echo "<p class='error'>❌ Some returns are missing approval requests</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If approval chain is missing, run: <code>fix_stock_return_gm_approval.php</code></li>";
echo "<li>Test by creating a new stock return with 'Good' condition</li>";
echo "<li>Log in as GM and approve it in Approvals section</li>";
echo "<li>Verify stock was added back to inventory</li>";
echo "<li>Test with 'Damaged' condition and verify it's marked as disposed</li>";
echo "</ol>";
