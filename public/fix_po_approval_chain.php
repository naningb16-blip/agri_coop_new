<?php
// Fix purchase order approval chain - GM only
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Purchase Order Approval Chain Fix</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style>";

try {
    // 1. Check if approval chain exists
    $chain = $db->fetchAll(
        "SELECT * FROM approval_chains WHERE module='purchase_order' ORDER BY step_order"
    );
    
    if (empty($chain)) {
        echo "<p class='info'>Creating GM-only approval chain for purchase orders...</p>";
        
        // Create GM-only approval chain
        $db->query(
            "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
             VALUES ('purchase_order', 1, 'gm', 'General Manager', 1)"
        );
        
        echo "<p class='success'>✓ Created approval chain for purchase_order module</p>";
    } else {
        echo "<p class='info'>Approval chain already exists:</p>";
        echo "<ul>";
        foreach ($chain as $step) {
            echo "<li>Step {$step['step_order']}: {$step['label']} ({$step['approver_role']})</li>";
        }
        echo "</ul>";
        
        // Check if it's GM-only
        if (count($chain) > 1) {
            echo "<p class='error'>⚠️ Multiple approval steps found. Updating to GM-only...</p>";
            
            // Delete all steps
            $db->query("DELETE FROM approval_chains WHERE module='purchase_order'");
            
            // Create GM-only step
            $db->query(
                "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
                 VALUES ('purchase_order', 1, 'gm', 'General Manager', 1)"
            );
            
            echo "<p class='success'>✓ Updated to GM-only approval</p>";
        }
    }
    
    // 2. Check existing pending POs
    $pendingPOs = $db->fetchAll(
        "SELECT po.*, s.name as supplier_name, ar.id as approval_id, ar.status as approval_status
         FROM purchase_orders po
         LEFT JOIN suppliers s ON po.supplier_id = s.id
         LEFT JOIN approval_requests ar ON ar.reference_type='purchase_order' AND ar.reference_id=po.id
         WHERE po.status='pending'
         ORDER BY po.created_at DESC"
    );
    
    echo "<h3>Pending Purchase Orders</h3>";
    
    if (empty($pendingPOs)) {
        echo "<p class='info'>No pending purchase orders found.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>PO ID</th><th>Supplier</th><th>Total</th><th>Approval Status</th><th>Action</th></tr>";
        
        foreach ($pendingPOs as $po) {
            echo "<tr>";
            echo "<td>{$po['id']}</td>";
            echo "<td>" . htmlspecialchars($po['supplier_name'] ?? 'N/A') . "</td>";
            echo "<td>₱" . number_format($po['total_amount'], 2) . "</td>";
            
            if ($po['approval_id']) {
                echo "<td>{$po['approval_status']}</td>";
                echo "<td>Has approval request</td>";
            } else {
                echo "<td class='error'>No approval request</td>";
                echo "<td class='error'>Missing approval!</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Check warehouses
    echo "<h3>Warehouse Check</h3>";
    $warehouses = $db->fetchAll("SELECT * FROM warehouses ORDER BY id");
    
    if (empty($warehouses)) {
        echo "<p class='error'>❌ CRITICAL: No warehouses found!</p>";
        echo "<p>You MUST create at least one warehouse before approving purchase orders.</p>";
        echo "<p><strong>How to create a warehouse:</strong></p>";
        echo "<ol>";
        echo "<li>Log in as admin or inventory user</li>";
        echo "<li>Go to Inventory module</li>";
        echo "<li>Click on 'Warehouses' tab</li>";
        echo "<li>Click 'Add Warehouse' button</li>";
        echo "<li>Enter name (e.g., 'Main Warehouse') and location</li>";
        echo "<li>Save</li>";
        echo "</ol>";
        echo "<p>Or run this SQL:</p>";
        echo "<pre>INSERT INTO warehouses (name, location) VALUES ('Main Warehouse', 'Main Office');</pre>";
    } else {
        echo "<p class='success'>✓ Found " . count($warehouses) . " warehouse(s)</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Location</th></tr>";
        foreach ($warehouses as $w) {
            echo "<tr>";
            echo "<td>{$w['id']}</td>";
            echo "<td>" . htmlspecialchars($w['name']) . "</td>";
            echo "<td>" . htmlspecialchars($w['location'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='info'>Approved POs will add stock to warehouse ID: {$warehouses[0]['id']}</p>";
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p class='success'>✅ Purchase order approval chain is configured (GM-only)</p>";
    
    if (empty($warehouses)) {
        echo "<p class='error'>❌ No warehouses - CREATE ONE BEFORE APPROVING POs!</p>";
    } else {
        echo "<p class='success'>✅ Warehouses configured</p>";
    }
    
    echo "<h3>How It Works Now:</h3>";
    echo "<ol>";
    echo "<li>Purchasing user creates a purchase order with items</li>";
    echo "<li>System automatically creates approval request for GM</li>";
    echo "<li>GM sees it in Approvals section</li>";
    echo "<li>GM approves the purchase order</li>";
    echo "<li><strong>System automatically adds all items to inventory (warehouse ID {$warehouses[0]['id'] ?? 'N/A'})</strong></li>";
    echo "<li>Stock movements created with reference to PO</li>";
    echo "<li>Products auto-created if they don't exist</li>";
    echo "</ol>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    if (empty($warehouses)) {
        echo "<li class='error'><strong>CREATE A WAREHOUSE FIRST!</strong></li>";
    }
    echo "<li>Create a new purchase order as purchasing user</li>";
    echo "<li>Log in as GM</li>";
    echo "<li>Go to Approvals section</li>";
    echo "<li>Click on the PO approval request</li>";
    echo "<li>Click 'Approve' button</li>";
    echo "<li>Go to Inventory module</li>";
    echo "<li>Check Stock tab - items should be there</li>";
    echo "<li>Check Movements tab - should show 'Auto stock-in from approved PO #X'</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
