<?php
/**
 * Test GM Stock Return Approval - Simple Test Page
 * This will show you exactly what's happening and what to do
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>GM Stock Return Test</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 5px solid #ccc; }
.box.success { border-left-color: #28a745; background: #d4edda; }
.box.error { border-left-color: #dc3545; background: #f8d7da; }
.box.warning { border-left-color: #ffc107; background: #fff3cd; }
.box.info { border-left-color: #17a2b8; background: #d1ecf1; }
h1 { color: #333; }
h2 { color: #555; margin-top: 30px; }
.btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; font-weight: bold; }
.btn:hover { background: #0056b3; }
.btn-success { background: #28a745; }
.btn-warning { background: #ffc107; color: #000; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; }
</style></head><body>";

echo "<h1>🧪 GM Stock Return Approval Test</h1>";

// Step 1: Check if logged in
if (!isset($_SESSION['user'])) {
    echo "<div class='box error'>";
    echo "<h2>❌ Step 1: Not Logged In</h2>";
    echo "<p>You need to login first!</p>";
    echo "<p><a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/login' class='btn'>Go to Login</a></p>";
    echo "</div></body></html>";
    exit;
}

$user = $_SESSION['user'];
echo "<div class='box success'>";
echo "<h2>✅ Step 1: Logged In</h2>";
echo "<p><strong>User:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
echo "<p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>";
echo "</div>";

// Step 2: Check if user is GM
if ($user['role'] !== 'gm') {
    echo "<div class='box warning'>";
    echo "<h2>⚠️ Step 2: Not GM Role</h2>";
    echo "<p>Your role is '<strong>{$user['role']}</strong>' but you need to be '<strong>gm</strong>' to approve stock returns.</p>";
    echo "<p>The code allows 'admin', 'gm', and 'manager' roles to approve.</p>";
    echo "</div>";
} else {
    echo "<div class='box success'>";
    echo "<h2>✅ Step 2: You Are GM</h2>";
    echo "<p>Your role is correct for approving stock returns.</p>";
    echo "</div>";
}

// Step 3: Check approval chain
$chain = $db->fetchOne(
    "SELECT * FROM approval_chains WHERE module='stock_return' AND approver_role='gm'"
);

if (!$chain) {
    echo "<div class='box error'>";
    echo "<h2>❌ Step 3: Approval Chain Missing</h2>";
    echo "<p>The approval chain for stock returns is not configured!</p>";
    echo "<p><a href='fix_stock_return_gm_approval.php' class='btn btn-warning'>Run Setup Script</a></p>";
    echo "</div>";
} else {
    echo "<div class='box success'>";
    echo "<h2>✅ Step 3: Approval Chain Exists</h2>";
    echo "<p>Stock return approval chain is configured for GM approval.</p>";
    echo "</div>";
}

// Step 4: Check for pending stock returns
$returns = $db->fetchAll(
    "SELECT sr.*, p.name as product_name
     FROM stock_returns sr
     JOIN products p ON sr.product_id = p.id
     WHERE sr.status='pending'
     ORDER BY sr.created_at DESC"
);

if (empty($returns)) {
    echo "<div class='box info'>";
    echo "<h2>ℹ️ Step 4: No Pending Stock Returns</h2>";
    echo "<p>There are no pending stock returns to approve.</p>";
    echo "<p>Create a stock return first, then come back here.</p>";
    echo "</div>";
} else {
    echo "<div class='box info'>";
    echo "<h2>📦 Step 4: Found " . count($returns) . " Pending Stock Returns</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Qty</th><th>Condition</th><th>Has Approval Request?</th><th>Action</th></tr>";
    
    $needsApprovalCreation = false;
    
    foreach ($returns as $ret) {
        $approval = $db->fetchOne(
            "SELECT id, status FROM approval_requests 
             WHERE reference_type='stock_return' AND reference_id=?",
            [$ret['id']], 'i'
        );
        
        echo "<tr>";
        echo "<td>#{$ret['id']}</td>";
        echo "<td>" . htmlspecialchars($ret['product_name']) . "</td>";
        echo "<td>{$ret['quantity']}</td>";
        echo "<td>" . htmlspecialchars($ret['condition_type']) . "</td>";
        
        if ($approval) {
            echo "<td style='color: green;'><strong>YES</strong> (#{$approval['id']}, {$approval['status']})</td>";
            echo "<td><a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/approvals/detail?id={$approval['id']}' class='btn btn-success'>View Approval</a></td>";
        } else {
            echo "<td style='color: red;'><strong>NO</strong></td>";
            echo "<td>—</td>";
            $needsApprovalCreation = true;
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    if ($needsApprovalCreation) {
        echo "<div class='box error'>";
        echo "<h2>❌ Step 5: Some Stock Returns Missing Approval Requests</h2>";
        echo "<p>Some stock returns don't have approval requests created.</p>";
        echo "<p><a href='create_missing_stock_return_approvals.php' class='btn btn-warning'>Create Missing Approvals</a></p>";
        echo "</div>";
    } else {
        echo "<div class='box success'>";
        echo "<h2>✅ Step 5: All Stock Returns Have Approval Requests</h2>";
        echo "<p>Click 'View Approval' buttons above to approve/reject each one.</p>";
        echo "</div>";
    }
}

// Step 6: Check if code is deployed
echo "<div class='box info'>";
echo "<h2>🔍 Step 6: Check If Code Is Deployed</h2>";
echo "<p>The approval permission fix is in the code repository, but Render may not have deployed it yet.</p>";
echo "<p><strong>To verify:</strong></p>";
echo "<ol>";
echo "<li>Click on any 'View Approval' button above</li>";
echo "<li>Look for the <strong>'Take Action'</strong> panel with Approve/Reject buttons</li>";
echo "<li>If you DON'T see it, Render hasn't deployed the updated code</li>";
echo "</ol>";
echo "</div>";

// Final summary
echo "<div class='box info'>";
echo "<h2>📋 What To Do Next</h2>";

if (!$chain) {
    echo "<p><strong>1. Run the setup script:</strong> <a href='fix_stock_return_gm_approval.php' class='btn'>Setup Approval Chain</a></p>";
}

if (!empty($returns)) {
    $needsApproval = false;
    foreach ($returns as $ret) {
        $approval = $db->fetchOne(
            "SELECT id FROM approval_requests WHERE reference_type='stock_return' AND reference_id=?",
            [$ret['id']], 'i'
        );
        if (!$approval) {
            $needsApproval = true;
            break;
        }
    }
    
    if ($needsApproval) {
        echo "<p><strong>2. Create approval requests:</strong> <a href='create_missing_stock_return_approvals.php' class='btn'>Create Approvals</a></p>";
    }
}

echo "<p><strong>3. Go to Approvals section:</strong> <a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/approvals' class='btn btn-success'>View All Approvals</a></p>";
echo "<p><strong>4. Check system status:</strong> <a href='system_status_check.php' class='btn'>System Status</a></p>";

echo "</div>";

echo "<hr>";
echo "<p><small>Generated: " . date('Y-m-d H:i:s') . "</small></p>";

echo "</body></html>";
