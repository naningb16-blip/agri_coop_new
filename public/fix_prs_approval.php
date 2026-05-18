<?php
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Purchase Requisition Approval Chain Fix</h2>";

// Check if PRS approval chain exists
$existing = $db->fetchOne(
    "SELECT COUNT(*) as count FROM approval_chains WHERE module='prs'",
    [], ''
);

if ($existing['count'] > 0) {
    echo "<p style='color:orange'>⚠️ PRS approval chain already exists. Deleting old chain...</p>";
    $db->query("DELETE FROM approval_chains WHERE module='prs'", [], '');
}

// Insert GM-only approval chain for PRS
$db->insert(
    "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?,?,?,?,?)",
    ['prs', 1, 'gm', 'General Manager', 1],
    'sissi'
);

echo "<p style='color:green'>✓ PRS approval chain created: GM approval only</p>";

// Check pending PRS requests without approval steps
$pendingPRS = $db->fetchAll(
    "SELECT ar.id, ar.reference_id 
     FROM approval_requests ar
     WHERE ar.module='prs' AND ar.status='pending'
     AND NOT EXISTS (SELECT 1 FROM approval_steps WHERE request_id=ar.id)",
    [], ''
);

if (!empty($pendingPRS)) {
    echo "<p style='color:blue'>Found " . count($pendingPRS) . " pending PRS requests without approval steps. Creating steps...</p>";
    
    foreach ($pendingPRS as $req) {
        $db->insert(
            "INSERT INTO approval_steps (request_id, step_order, approver_role, label, status) VALUES (?,?,?,?,?)",
            [$req['id'], 1, 'gm', 'General Manager', 'pending'],
            'iisss'
        );
        echo "<p>✓ Created GM approval step for request #{$req['id']}</p>";
    }
}

echo "<h3>Summary:</h3>";
echo "<p>✓ Purchase Requisitions now require GM approval only</p>";
echo "<p>✓ GM can approve PRS requests in the Approvals section</p>";
echo "<p><a href='/purchasing?tab=prs'>Go to Purchase Requisitions</a></p>";
?>
