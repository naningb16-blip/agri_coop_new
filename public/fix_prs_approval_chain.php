<?php
// Create approval chain for purchase requisitions (PRS)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Purchase Requisition (PRS) Approval Chain Setup</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    // Check if approval chain already exists
    $existing = $db->fetchOne("SELECT * FROM approval_chains WHERE module='prs'");
    
    if ($existing) {
        echo "<p class='info'>ℹ️ Approval chain for PRS already exists. Deleting old chain...</p>";
        $db->query("DELETE FROM approval_chains WHERE module='prs'");
    }
    
    // Create GM-only approval chain for PRS
    echo "<h3>Creating GM-only approval chain for Purchase Requisitions</h3>";
    $db->query(
        "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?,?,?,?,?)",
        ['prs', 1, 'gm', 'General Manager', 1],
        'sissi'
    );
    echo "<p class='success'>✓ Approval chain created: PRS → GM</p>";
    
    // Fix existing pending approval requests
    echo "<h3>Fixing existing PRS approval requests</h3>";
    $pendingRequests = $db->fetchAll(
        "SELECT ar.* FROM approval_requests ar
         WHERE ar.module='prs' AND ar.status='pending'"
    );
    
    if (empty($pendingRequests)) {
        echo "<p class='info'>No pending PRS approval requests to fix</p>";
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
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p class='success'>✅ PRS approval chain is now set up (GM-only)</p>";
    echo "<p class='info'>📋 GM can now approve purchase requisitions in the Approvals section</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Log in as GM</li>";
    echo "<li>Go to Approvals section</li>";
    echo "<li>You should see pending purchase requisitions</li>";
    echo "<li>Click on each one and approve/reject</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
