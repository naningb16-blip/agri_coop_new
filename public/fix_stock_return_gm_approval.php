<?php
// Fix stock return approval system - GM only approval
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Stock Return Approval System Fix</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style>";

try {
    // 1. Check if approval chain exists
    $chain = $db->fetchAll(
        "SELECT * FROM approval_chains WHERE module='stock_return' ORDER BY step_order"
    );
    
    if (empty($chain)) {
        echo "<p class='info'>Creating GM-only approval chain for stock returns...</p>";
        
        // Create GM-only approval chain
        $db->query(
            "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
             VALUES ('stock_return', 1, 'gm', 'General Manager', 1)"
        );
        
        echo "<p class='success'>✓ Created approval chain for stock_return module</p>";
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
            $db->query("DELETE FROM approval_chains WHERE module='stock_return'");
            
            // Create GM-only step
            $db->query(
                "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
                 VALUES ('stock_return', 1, 'gm', 'General Manager', 1)"
            );
            
            echo "<p class='success'>✓ Updated to GM-only approval</p>";
        }
    }
    
    // 2. Check existing stock returns
    $returns = $db->fetchAll(
        "SELECT sr.*, ar.id as approval_id, ar.status as approval_status
         FROM stock_returns sr
         LEFT JOIN approval_requests ar ON ar.reference_type='stock_return' AND ar.reference_id=sr.id
         WHERE sr.status='pending'
         ORDER BY sr.created_at DESC"
    );
    
    echo "<h3>Pending Stock Returns</h3>";
    
    if (empty($returns)) {
        echo "<p class='info'>No pending stock returns found.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Product</th><th>Quantity</th><th>Condition</th><th>Approval Status</th><th>Action</th></tr>";
        
        foreach ($returns as $ret) {
            $product = $db->fetchOne("SELECT name FROM products WHERE id=?", [$ret['product_id']], 'i');
            $productName = $product['name'] ?? 'Unknown';
            
            echo "<tr>";
            echo "<td>{$ret['id']}</td>";
            echo "<td>{$productName}</td>";
            echo "<td>{$ret['quantity']}</td>";
            echo "<td>{$ret['condition_type']}</td>";
            
            if ($ret['approval_id']) {
                echo "<td>{$ret['approval_status']}</td>";
                echo "<td>Has approval request</td>";
            } else {
                echo "<td class='error'>No approval request</td>";
                echo "<td class='error'>Missing approval!</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p class='success'>✅ Stock return approval chain is configured (GM-only)</p>";
    echo "<p class='info'>Stock returns now require GM approval through the Approvals section</p>";
    echo "<p class='info'>When GM approves, the system will automatically restock or dispose based on condition</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test by creating a new stock return in Inventory section</li>";
    echo "<li>GM should see it in Approvals section</li>";
    echo "<li>When GM approves, stock should be automatically restocked (good condition) or marked for disposal (damaged)</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
