<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Fixing Inventory Release Approval System</h2>";

// Fix 1: Ensure approval chain exists
echo "<h3>Step 1: Checking/Adding Approval Chain</h3>";
$existing = $db->fetchAll("SELECT * FROM approval_chains WHERE module='stock_release'");

// Remove old 2-step chain if it exists
if (!empty($existing) && count($existing) > 1) {
    echo "<p>Removing old 2-step approval chain...</p>";
    $db->query("DELETE FROM approval_chains WHERE module='stock_release'");
    $existing = [];
}

if (empty($existing)) {
    echo "<p>Adding GM-only approval chain for stock_release...</p>";
    $db->query(
        "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
         ('stock_release', 1, 'gm', 'General Manager', 1)"
    );
    echo "<p style='color:green;'>✅ Approval chain added successfully! (GM only - single step)</p>";
} else {
    echo "<p style='color:green;'>✅ Approval chain already exists.</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Is GM Step</th></tr>";
    foreach ($existing as $step) {
        echo "<tr><td>{$step['step_order']}</td><td>{$step['approver_role']}</td><td>{$step['label']}</td><td>" . ($step['is_gm_step'] ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
}

// Fix 2: Add requesting_department column if missing
echo "<h3>Step 2: Checking requesting_department Column</h3>";
$columns = $db->fetchAll("DESCRIBE stock_release_requests");
$hasColumn = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'requesting_department') {
        $hasColumn = true;
        break;
    }
}

if (!$hasColumn) {
    echo "<p>Adding requesting_department column...</p>";
    $db->query("ALTER TABLE stock_release_requests ADD COLUMN requesting_department VARCHAR(50) NULL AFTER purpose");
    echo "<p style='color:green;'>✅ Column added successfully!</p>";
} else {
    echo "<p style='color:green;'>✅ Column already exists.</p>";
}

// Fix 3: Create approval requests for any pending release requests that don't have them
echo "<h3>Step 3: Creating Missing Approval Requests</h3>";
$orphanedRequests = $db->fetchAll(
    "SELECT srr.*, p.name as product_name, w.name as warehouse_name
     FROM stock_release_requests srr
     JOIN products p ON srr.product_id = p.id
     JOIN warehouses w ON srr.warehouse_id = w.id
     LEFT JOIN approval_requests ar ON ar.reference_type='stock_release' AND ar.reference_id=srr.id
     WHERE srr.status='pending' AND ar.id IS NULL"
);

if (empty($orphanedRequests)) {
    echo "<p style='color:green;'>✅ All pending release requests have approval requests.</p>";
} else {
    echo "<p>Found " . count($orphanedRequests) . " release requests without approval requests. Creating them now...</p>";
    
    require_once __DIR__ . '/../app/models/ApprovalModel.php';
    $approvalModel = new ApprovalModel();
    
    foreach ($orphanedRequests as $req) {
        $description = "Warehouse: {$req['warehouse_name']} | Purpose: {$req['purpose']}";
        if (!empty($req['requesting_department'])) {
            $description .= " | Department: {$req['requesting_department']}";
        }
        
        $approvalModel->createRequest([
            'module'         => 'stock_release',
            'reference_type' => 'stock_release',
            'reference_id'   => $req['id'],
            'title'          => "Stock Release: {$req['product_name']} x" . number_format($req['quantity'], 2),
            'description'    => $description,
        ], $req['requested_by']);
        
        echo "<p>✅ Created approval request for release #{$req['id']} - {$req['product_name']}</p>";
    }
    echo "<p style='color:green;'>✅ All missing approval requests created!</p>";
}

// Summary
echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p style='color:green; font-weight:bold;'>✅ Inventory release approval system has been fixed!</p>";
echo "<p>Changes made:</p>";
echo "<ul>";
echo "<li>Approval chain for stock_release module is in place (GM only - single step)</li>";
echo "<li>requesting_department column exists</li>";
echo "<li>All pending release requests now have approval requests</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test creating a new release request from the Inventory module</li>";
echo "<li>Verify it appears in the GM's pending approvals</li>";
echo "<li>Test the approval workflow end-to-end</li>";
echo "</ol>";

echo "<p><a href='/approvals'>Go to Approvals</a> | <a href='/inventory'>Go to Inventory</a></p>";
