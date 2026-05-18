<?php
/**
 * Debug Stock Return Approval - Check why GM can't approve
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Stock Return Approval Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.info { color: #17a2b8; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #f8f9fa; font-weight: 600; }
.code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h2>🔍 Stock Return Approval Debug</h2>";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo "<p class='error'>❌ You are not logged in!</p>";
    echo "<p>Please <a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/login'>login</a> first.</p>";
    echo "</div></body></html>";
    exit;
}

$user = $_SESSION['user'];
echo "<h3>👤 Current User</h3>";
echo "<table>";
echo "<tr><th>User ID</th><td>{$user['id']}</td></tr>";
echo "<tr><th>Full Name</th><td>" . htmlspecialchars($user['full_name']) . "</td></tr>";
echo "<tr><th>Role</th><td><strong>" . htmlspecialchars($user['role']) . "</strong></td></tr>";
echo "</table>";

// Check approval chain
echo "<h3>⛓️ Stock Return Approval Chain</h3>";
$chain = $db->fetchAll(
    "SELECT * FROM approval_chains WHERE module='stock_return' ORDER BY step_order"
);

if (empty($chain)) {
    echo "<p class='error'>❌ No approval chain configured for stock_return module!</p>";
    echo "<p><a href='fix_stock_return_gm_approval.php'>Run Setup Script</a></p>";
} else {
    echo "<table>";
    echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th></tr>";
    foreach ($chain as $c) {
        echo "<tr>";
        echo "<td>{$c['step_order']}</td>";
        echo "<td><strong>{$c['approver_role']}</strong></td>";
        echo "<td>{$c['label']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='success'>✅ Approval chain is configured</p>";
}

// Check pending stock returns
echo "<h3>📦 Pending Stock Returns</h3>";
$returns = $db->fetchAll(
    "SELECT sr.*, p.name as product_name, u.full_name as created_by_name
     FROM stock_returns sr
     JOIN products p ON sr.product_id = p.id
     JOIN users u ON sr.created_by = u.id
     WHERE sr.status='pending'
     ORDER BY sr.created_at DESC"
);

if (empty($returns)) {
    echo "<p class='info'>ℹ️ No pending stock returns</p>";
} else {
    echo "<p>Found " . count($returns) . " pending stock returns:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Product</th><th>Qty</th><th>Condition</th><th>Created By</th><th>Has Approval?</th><th>Approval Status</th></tr>";
    
    foreach ($returns as $ret) {
        $approval = $db->fetchOne(
            "SELECT ar.*, acs.status as current_step_status, acs.approver_role
             FROM approval_requests ar
             LEFT JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
             WHERE ar.reference_type='stock_return' AND ar.reference_id=?",
            [$ret['id']], 'i'
        );
        
        echo "<tr>";
        echo "<td>{$ret['id']}</td>";
        echo "<td>" . htmlspecialchars($ret['product_name']) . "</td>";
        echo "<td>{$ret['quantity']}</td>";
        echo "<td>" . htmlspecialchars($ret['condition_type']) . "</td>";
        echo "<td>" . htmlspecialchars($ret['created_by_name']) . "</td>";
        
        if ($approval) {
            echo "<td class='success'>YES (#{$approval['id']})</td>";
            echo "<td>";
            echo "Request: <strong>{$approval['status']}</strong><br>";
            echo "Current Step: {$approval['current_step']}<br>";
            echo "Step Status: <strong>{$approval['current_step_status']}</strong><br>";
            echo "Approver Role: <strong>{$approval['approver_role']}</strong>";
            echo "</td>";
        } else {
            echo "<td class='error'>NO</td>";
            echo "<td class='error'>No approval request!</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
}

// Check specific approval request if provided
if (isset($_GET['request_id'])) {
    $requestId = (int)$_GET['request_id'];
    
    echo "<h3>🔎 Detailed Check for Approval Request #{$requestId}</h3>";
    
    $request = $db->fetchOne(
        "SELECT * FROM approval_requests WHERE id=?",
        [$requestId], 'i'
    );
    
    if (!$request) {
        echo "<p class='error'>❌ Approval request not found!</p>";
    } else {
        echo "<h4>Request Details</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><td>{$request['id']}</td></tr>";
        echo "<tr><th>Module</th><td>{$request['module']}</td></tr>";
        echo "<tr><th>Reference</th><td>{$request['reference_type']} #{$request['reference_id']}</td></tr>";
        echo "<tr><th>Status</th><td><strong>{$request['status']}</strong></td></tr>";
        echo "<tr><th>Current Step</th><td><strong>{$request['current_step']}</strong></td></tr>";
        echo "</table>";
        
        // Get steps
        $steps = $db->fetchAll(
            "SELECT * FROM approval_steps WHERE request_id=? ORDER BY step_order",
            [$requestId], 'i'
        );
        
        echo "<h4>Approval Steps</h4>";
        echo "<table>";
        echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Status</th><th>Actioned By</th></tr>";
        foreach ($steps as $step) {
            $isCurrent = ($step['step_order'] == $request['current_step']);
            $rowClass = $isCurrent ? "style='background: #fff3cd;'" : "";
            
            echo "<tr $rowClass>";
            echo "<td>" . ($isCurrent ? "→ " : "") . "{$step['step_order']}</td>";
            echo "<td><strong>{$step['approver_role']}</strong></td>";
            echo "<td>{$step['label']}</td>";
            echo "<td><strong>{$step['status']}</strong></td>";
            echo "<td>" . ($step['actioned_by'] ? "User #{$step['actioned_by']}" : "—") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if current user can act
        echo "<h4>Can Current User Act?</h4>";
        
        if ($request['status'] !== 'pending') {
            echo "<p class='warning'>⚠️ Request is not pending (status: {$request['status']})</p>";
        } else {
            $currentStep = null;
            foreach ($steps as $s) {
                if ($s['step_order'] == $request['current_step'] && $s['status'] === 'pending') {
                    $currentStep = $s;
                    break;
                }
            }
            
            if (!$currentStep) {
                echo "<p class='error'>❌ No active pending step found!</p>";
            } else {
                echo "<p>Current pending step requires: <strong>{$currentStep['approver_role']}</strong></p>";
                echo "<p>Your role: <strong>{$user['role']}</strong></p>";
                
                // Check permission logic from ApprovalController
                $canAct = in_array($user['role'], ['admin', 'gm', 'manager']) || $user['role'] === $currentStep['approver_role'];
                
                echo "<div class='code'>";
                echo "Permission Check:<br>";
                echo "in_array('{$user['role']}', ['admin', 'gm', 'manager']) = " . (in_array($user['role'], ['admin', 'gm', 'manager']) ? 'TRUE' : 'FALSE') . "<br>";
                echo "'{$user['role']}' === '{$currentStep['approver_role']}' = " . ($user['role'] === $currentStep['approver_role'] ? 'TRUE' : 'FALSE') . "<br>";
                echo "<strong>Result: " . ($canAct ? 'CAN ACT' : 'CANNOT ACT') . "</strong>";
                echo "</div>";
                
                if ($canAct) {
                    echo "<p class='success'>✅ You SHOULD be able to approve/reject this request!</p>";
                    echo "<p>If you don't see the buttons, the issue is with deployment (Render not serving updated code).</p>";
                } else {
                    echo "<p class='error'>❌ You CANNOT act on this request based on current logic.</p>";
                    echo "<p>Your role '{$user['role']}' doesn't match required role '{$currentStep['approver_role']}'</p>";
                }
            }
        }
    }
}

// Summary
echo "<h3>📋 Summary</h3>";

$issues = [];
$fixes = [];

if (empty($chain)) {
    $issues[] = "No approval chain configured for stock_return";
    $fixes[] = "Run <a href='fix_stock_return_gm_approval.php'>fix_stock_return_gm_approval.php</a>";
}

if (!empty($returns)) {
    $returnsWithoutApproval = 0;
    foreach ($returns as $ret) {
        $approval = $db->fetchOne(
            "SELECT id FROM approval_requests WHERE reference_type='stock_return' AND reference_id=?",
            [$ret['id']], 'i'
        );
        if (!$approval) {
            $returnsWithoutApproval++;
        }
    }
    
    if ($returnsWithoutApproval > 0) {
        $issues[] = "{$returnsWithoutApproval} pending stock returns don't have approval requests";
        $fixes[] = "Run <a href='create_missing_stock_return_approvals.php'>create_missing_stock_return_approvals.php</a>";
    }
}

if (!in_array($user['role'], ['admin', 'gm', 'manager'])) {
    $issues[] = "Your role '{$user['role']}' is not authorized to approve stock returns";
    $fixes[] = "Login as a user with 'gm', 'manager', or 'admin' role";
}

if (empty($issues)) {
    echo "<p class='success'>✅ Everything looks good! If you still don't see Approve/Reject buttons:</p>";
    echo "<ol>";
    echo "<li>Make sure you're viewing a <strong>pending</strong> approval request</li>";
    echo "<li>Check that the approval request exists for the stock return</li>";
    echo "<li>The issue may be Render deployment (code not deployed to live server)</li>";
    echo "<li>Try accessing the approval detail page directly</li>";
    echo "</ol>";
} else {
    echo "<p class='error'>❌ Found " . count($issues) . " issue(s):</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul>";
    
    echo "<p class='info'><strong>Fixes:</strong></p>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>{$fix}</li>";
    }
    echo "</ul>";
}

// Quick links
echo "<h3>🔗 Quick Actions</h3>";
echo "<p>";
echo "<a href='fix_stock_return_gm_approval.php' style='padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>Setup Approval Chain</a> ";
echo "<a href='create_missing_stock_return_approvals.php' style='padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>Create Missing Approvals</a> ";
echo "<a href='system_status_check.php' style='padding: 10px 15px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block;'>System Status</a>";
echo "</p>";

echo "<hr>";
echo "<p><small>Generated: " . date('Y-m-d H:i:s') . "</small></p>";

echo "</div></body></html>";
