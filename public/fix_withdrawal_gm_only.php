<?php
// Fix withdrawal approval chain to GM-only and process stuck withdrawals
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Withdrawal Approval Chain - GM Only Fix</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    // 1. Delete existing withdrawal approval chain
    echo "<h3>Step 1: Removing old approval chain</h3>";
    $db->query("DELETE FROM approval_chains WHERE module='withdrawal'");
    echo "<p class='success'>✓ Old approval chain removed</p>";

    // 2. Create new GM-only approval chain
    echo "<h3>Step 2: Creating GM-only approval chain</h3>";
    $db->query(
        "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?,?,?,?,?)",
        ['withdrawal', 1, 'gm', 'General Manager', 1],
        'sissi'
    );
    echo "<p class='success'>✓ New GM-only approval chain created</p>";

    // 3. Fix existing approval requests - update to step 1 (GM step)
    echo "<h3>Step 3: Fixing existing approval requests</h3>";
    $pendingRequests = $db->fetchAll(
        "SELECT ar.* FROM approval_requests ar
         WHERE ar.module='withdrawal' AND ar.status='pending'"
    );
    
    if (empty($pendingRequests)) {
        echo "<p class='info'>No pending approval requests to fix</p>";
    } else {
        foreach ($pendingRequests as $req) {
            // Delete old approval steps
            $db->query("DELETE FROM approval_steps WHERE request_id=?", [$req['id']], 'i');
            
            // Create new GM-only step
            $db->query(
                "INSERT INTO approval_steps (request_id, step_order, approver_role, label, status) VALUES (?,?,?,?,?)",
                [$req['id'], 1, 'gm', 'General Manager', 'pending'],
                'iisss'
            );
            
            // Update request to step 1
            $db->query(
                "UPDATE approval_requests SET current_step=1 WHERE id=?",
                [$req['id']], 'i'
            );
            
            echo "<p class='success'>✓ Fixed approval request #{$req['id']}: {$req['title']}</p>";
        }
    }

    // 4. Summary
    echo "<h3>Summary</h3>";
    echo "<p class='success'>✅ Withdrawal approval chain changed to GM-only</p>";
    echo "<p class='info'>📋 Existing pending withdrawals have been updated</p>";
    echo "<p class='info'>🎯 GM can now approve withdrawals directly in the Approvals section</p>";
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Log in as GM</li>";
    echo "<li>Go to Approvals section</li>";
    echo "<li>Approve the withdrawal requests</li>";
    echo "<li>Ledger entries will be created automatically and balance will be deducted</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
