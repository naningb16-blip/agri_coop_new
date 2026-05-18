<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Inventory Release Approval Diagnostic</h2>";

// Check 1: Approval chain for stock_release
echo "<h3>1. Approval Chain for stock_release module:</h3>";
$chain = $db->fetchAll("SELECT * FROM approval_chains WHERE module='stock_release' ORDER BY step_order");
if (empty($chain)) {
    echo "<p style='color:red;'>❌ NO approval chain found for stock_release module!</p>";
    echo "<p>This is the main issue - release requests won't route to GM without this chain.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Is GM Step</th></tr>";
    foreach ($chain as $step) {
        echo "<tr>";
        echo "<td>{$step['step_order']}</td>";
        echo "<td>{$step['approver_role']}</td>";
        echo "<td>{$step['label']}</td>";
        echo "<td>" . ($step['is_gm_step'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check 2: requesting_department column
echo "<h3>2. Stock Release Requests Table Structure:</h3>";
$columns = $db->fetchAll("DESCRIBE stock_release_requests");
$hasRequestingDept = false;
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
    if ($col['Field'] === 'requesting_department') {
        $hasRequestingDept = true;
    }
}
echo "</table>";

if (!$hasRequestingDept) {
    echo "<p style='color:orange;'>⚠️ requesting_department column is missing (optional feature)</p>";
}

// Check 3: Recent release requests and their approval status
echo "<h3>3. Recent Release Requests:</h3>";
$requests = $db->fetchAll(
    "SELECT srr.id, srr.status, srr.created_at, p.name as product_name, 
            u.full_name as requested_by_name,
            ar.id as approval_request_id, ar.status as approval_status, ar.current_step
     FROM stock_release_requests srr
     JOIN products p ON srr.product_id = p.id
     JOIN users u ON srr.requested_by = u.id
     LEFT JOIN approval_requests ar ON ar.reference_type='stock_release' AND ar.reference_id=srr.id
     ORDER BY srr.created_at DESC LIMIT 10"
);

if (empty($requests)) {
    echo "<p>No release requests found.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product</th><th>Requested By</th><th>Status</th><th>Approval ID</th><th>Approval Status</th><th>Current Step</th><th>Created</th></tr>";
    foreach ($requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>{$req['product_name']}</td>";
        echo "<td>{$req['requested_by_name']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>" . ($req['approval_request_id'] ?? 'N/A') . "</td>";
        echo "<td>" . ($req['approval_status'] ?? 'N/A') . "</td>";
        echo "<td>" . ($req['current_step'] ?? 'N/A') . "</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check 4: Pending approvals for GM
echo "<h3>4. Pending Approvals for GM Role:</h3>";
$gmApprovals = $db->fetchAll(
    "SELECT ar.id, ar.title, ar.module, ar.current_step, ar.created_at,
            acs.label as current_step_label, acs.approver_role
     FROM approval_requests ar
     JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
     WHERE ar.status = 'pending' AND acs.status = 'pending' AND acs.approver_role = 'gm'
     ORDER BY ar.created_at ASC"
);

if (empty($gmApprovals)) {
    echo "<p>No pending approvals for GM.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Module</th><th>Title</th><th>Current Step</th><th>Step Label</th><th>Created</th></tr>";
    foreach ($gmApprovals as $appr) {
        echo "<tr>";
        echo "<td>{$appr['id']}</td>";
        echo "<td>{$appr['module']}</td>";
        echo "<td>{$appr['title']}</td>";
        echo "<td>{$appr['current_step']}</td>";
        echo "<td>{$appr['current_step_label']}</td>";
        echo "<td>{$appr['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Summary:</h3>";
if (empty($chain)) {
    echo "<p style='color:red; font-weight:bold;'>❌ MAIN ISSUE: The approval_chains table is missing entries for 'stock_release' module.</p>";
    echo "<p>Solution: Run the inventory_migration.sql file to add the approval chain.</p>";
} else {
    echo "<p style='color:green;'>✅ Approval chain exists for stock_release.</p>";
    echo "<p>If release requests still aren't showing for GM, check:</p>";
    echo "<ul>";
    echo "<li>Are approval_requests being created when release requests are submitted?</li>";
    echo "<li>Are the approval steps being created correctly?</li>";
    echo "<li>Is the GM user's role set to 'gm' in the database?</li>";
    echo "</ul>";
}
