<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Update Stock Release Approval to GM Only</h2>";
echo "<p>This will change the approval workflow from 2-step (Manager → GM) to 1-step (GM only)</p>";
echo "<hr>";

try {
    // Step 1: Check current chain
    echo "<h3>Step 1: Current Approval Chain</h3>";
    $existing = $db->fetchAll("SELECT * FROM approval_chains WHERE module='stock_release' ORDER BY step_order");
    
    if (empty($existing)) {
        echo "<p style='color:orange;'>⚠️ No approval chain found for stock_release</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th></tr>";
        foreach ($existing as $step) {
            echo "<tr><td>{$step['step_order']}</td><td>{$step['approver_role']}</td><td>{$step['label']}</td></tr>";
        }
        echo "</table>";
        
        if (count($existing) > 1) {
            echo "<p style='color:orange;'>⚠️ Found multi-step chain. Will update to single GM-only step.</p>";
        } else if ($existing[0]['approver_role'] === 'gm') {
            echo "<p style='color:green;'>✅ Already configured for GM-only approval!</p>";
            echo "<p><a href='/inventory'>Go to Inventory</a> | <a href='/approvals'>Go to Approvals</a></p>";
            exit;
        }
    }
    
    // Step 2: Remove old chain
    echo "<h3>Step 2: Removing Old Approval Chain</h3>";
    $db->query("DELETE FROM approval_chains WHERE module = 'stock_release'");
    echo "<p style='color:green;'>✅ Old chain removed</p>";
    
    // Step 3: Add new GM-only chain
    echo "<h3>Step 3: Adding GM-Only Approval Chain</h3>";
    $db->query(
        "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
         ('stock_release', 1, 'gm', 'General Manager', 1)"
    );
    echo "<p style='color:green;'>✅ New GM-only chain added</p>";
    
    // Step 4: Update pending approval requests
    echo "<h3>Step 4: Updating Pending Approval Requests</h3>";
    
    // Get pending stock release approval requests
    $pendingRequests = $db->fetchAll(
        "SELECT ar.id, ar.title, ar.current_step
         FROM approval_requests ar
         WHERE ar.module = 'stock_release' AND ar.status = 'pending'"
    );
    
    if (empty($pendingRequests)) {
        echo "<p>No pending approval requests to update.</p>";
    } else {
        echo "<p>Found " . count($pendingRequests) . " pending approval requests. Updating...</p>";
        
        foreach ($pendingRequests as $req) {
            // Delete old steps
            $db->query(
                "DELETE FROM approval_steps WHERE request_id = ?",
                [$req['id']], 'i'
            );
            
            // Create new GM-only step
            $db->query(
                "INSERT INTO approval_steps (request_id, step_order, approver_role, label, status) 
                 VALUES (?, 1, 'gm', 'General Manager', 'pending')",
                [$req['id']], 'i'
            );
            
            // Reset current step to 1
            $db->query(
                "UPDATE approval_requests SET current_step = 1 WHERE id = ?",
                [$req['id']], 'i'
            );
            
            echo "<p>✅ Updated: {$req['title']}</p>";
        }
        
        echo "<p style='color:green;'>✅ All pending requests updated to GM-only approval</p>";
    }
    
    // Verification
    echo "<hr>";
    echo "<h3>Verification</h3>";
    
    $newChain = $db->fetchAll("SELECT * FROM approval_chains WHERE module='stock_release'");
    echo "<p><strong>New Approval Chain:</strong></p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Is GM Step</th></tr>";
    foreach ($newChain as $step) {
        echo "<tr><td>{$step['step_order']}</td><td>{$step['approver_role']}</td><td>{$step['label']}</td><td>" . ($step['is_gm_step'] ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
    
    $pendingForGM = $db->fetchAll(
        "SELECT ar.id, ar.title, ar.current_step, acs.approver_role
         FROM approval_requests ar
         JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
         WHERE ar.module = 'stock_release' AND ar.status = 'pending'"
    );
    
    echo "<p><strong>Pending Approvals for GM:</strong></p>";
    if (empty($pendingForGM)) {
        echo "<p>No pending approvals.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Current Step</th><th>Approver Role</th></tr>";
        foreach ($pendingForGM as $appr) {
            echo "<tr><td>{$appr['id']}</td><td>{$appr['title']}</td><td>{$appr['current_step']}</td><td>{$appr['approver_role']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3 style='color:green;'>✅ Update Complete!</h3>";
    echo "<p>Stock release requests now go directly to GM for approval (single step).</p>";
    echo "<p><a href='/inventory'>Go to Inventory</a> | <a href='/approvals'>Go to Approvals</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
