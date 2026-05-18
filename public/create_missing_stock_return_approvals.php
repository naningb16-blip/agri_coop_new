<?php
/**
 * Create Approval Requests for Existing Stock Returns
 * This adds approval requests to stock returns that were created before the approval chain existed
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Create Missing Stock Return Approval Requests</h2>";
echo "<style>
body { font-family: Arial; padding: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style>";

try {
    // Find stock returns without approval requests
    $returns = $db->fetchAll(
        "SELECT sr.*, p.name as product_name, u.full_name as created_by_name
         FROM stock_returns sr
         JOIN products p ON sr.product_id = p.id
         JOIN users u ON sr.created_by = u.id
         LEFT JOIN approval_requests ar ON ar.reference_type='stock_return' AND ar.reference_id=sr.id
         WHERE sr.status='pending' AND ar.id IS NULL
         ORDER BY sr.created_at DESC"
    );
    
    if (empty($returns)) {
        echo "<p class='success'>✅ All pending stock returns already have approval requests!</p>";
        echo "<p><a href='fix_stock_return_gm_approval.php'>← Back to Stock Return Fix</a></p>";
        exit;
    }
    
    echo "<p class='info'>Found " . count($returns) . " stock returns without approval requests:</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Quantity</th><th>Condition</th><th>Reason</th><th>Created By</th><th>Created At</th></tr>";
    
    foreach ($returns as $ret) {
        echo "<tr>";
        echo "<td>{$ret['id']}</td>";
        echo "<td>" . htmlspecialchars($ret['product_name']) . "</td>";
        echo "<td>{$ret['quantity']}</td>";
        echo "<td>" . htmlspecialchars($ret['condition_type']) . "</td>";
        echo "<td>" . htmlspecialchars($ret['reason']) . "</td>";
        echo "<td>" . htmlspecialchars($ret['created_by_name']) . "</td>";
        echo "<td>" . date('M d, Y H:i', strtotime($ret['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show form to create approval requests
    if (!isset($_POST['confirm'])) {
        echo "<form method='POST'>";
        echo "<p><strong>This will create GM approval requests for all " . count($returns) . " stock returns listed above.</strong></p>";
        echo "<p>After creating the requests, GM will be able to approve them from the Approvals section.</p>";
        echo "<input type='hidden' name='confirm' value='1'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; font-size: 16px;'>Create Approval Requests</button>";
        echo "</form>";
        exit;
    }
    
    // Create approval requests
    echo "<hr>";
    echo "<h3>Creating Approval Requests...</h3>";
    
    $created = 0;
    $errors = 0;
    
    foreach ($returns as $ret) {
        try {
            // Create approval request
            $title = "Stock Return: {$ret['product_name']} ({$ret['quantity']} units)";
            
            $db->query(
                "INSERT INTO approval_requests (module, reference_type, reference_id, title, requested_by, current_step, status)
                 VALUES ('stock_return', 'stock_return', ?, ?, ?, 1, 'pending')",
                [$ret['id'], $title, $ret['created_by']]
            );
            
            $request_id = $db->getConnection()->insert_id;
            
            // Create GM approval step
            $db->query(
                "INSERT INTO approval_steps (request_id, step_order, approver_role, label, status)
                 VALUES (?, 1, 'gm', 'General Manager', 'pending')",
                [$request_id], 'i'
            );
            
            echo "<p class='success'>✅ Created approval request for Stock Return #{$ret['id']} - {$ret['product_name']}</p>";
            $created++;
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Failed for Stock Return #{$ret['id']}: " . htmlspecialchars($e->getMessage()) . "</p>";
            $errors++;
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p class='success'>✅ Successfully created {$created} approval requests</p>";
    
    if ($errors > 0) {
        echo "<p class='error'>❌ Failed to create {$errors} approval requests</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Login as GM user</li>";
    echo "<li>Go to Approvals section</li>";
    echo "<li>You should see all {$created} stock return approval requests</li>";
    echo "<li>Approve or reject each one</li>";
    echo "<li>Approved returns will automatically restock (good condition) or mark for disposal (damaged)</li>";
    echo "</ol>";
    
    echo "<p><a href='fix_stock_return_gm_approval.php'>← Back to Stock Return Fix</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
