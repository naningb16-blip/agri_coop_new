<?php
// Fix Operational Department Approval Workflow
// Adds GM approval chain for operational module

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Operational Approval Workflow Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if operational approval chain exists
    $result = $conn->query("SELECT * FROM approval_chains WHERE module='operational'");
    $exists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- Operational approval chain: " . ($exists ? "EXISTS" : "MISSING") . "\n\n";
    
    if ($exists) {
        echo "✓ Approval chain already exists!\n\n";
        echo "Existing chains:\n";
        $result = $conn->query("SELECT * FROM approval_chains WHERE module='operational'");
        while ($row = $result->fetch_assoc()) {
            echo "  - Step {$row['step_order']}: {$row['label']} (Role: {$row['approver_role']})\n";
        }
    } else {
        echo "Adding approval chain...\n\n";
        
        // Add GM approval chain for operational module
        $stmt = $conn->prepare("INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?, ?, ?, ?, ?)");
        $module = 'operational';
        $step = 1;
        $role = 'gm';
        $label = 'General Manager Approval';
        $isGm = 1;
        $stmt->bind_param('sissi', $module, $step, $role, $label, $isGm);
        $stmt->execute();
        
        echo "✓ GM approval chain added for operational module\n\n";
    }
    
    // Check existing approval requests
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            reference_type,
            COUNT(*) AS total,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected
        FROM approval_requests
        WHERE module='operational'
        GROUP BY reference_type
    ");
    
    if ($result->num_rows > 0) {
        echo "Operational Approval Requests:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['reference_type']}: {$row['total']} total ({$row['pending']} pending, {$row['approved']} approved, {$row['rejected']} rejected)\n";
        }
    } else {
        echo "No operational approval requests found yet.\n";
        echo "Approval requests will be created when:\n";
        echo "- New production records are created\n";
        echo "- New processing batches are created\n";
    }
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's changed:\n";
    echo "1. GM approval chain added for operational module\n";
    echo "2. Production records now require GM approval before planting\n";
    echo "3. Processing batches now require GM approval before processing\n";
    echo "4. GM can see operational requests in Approvals module\n";
    echo "\nNext steps:\n";
    echo "1. Create a new production record or processing batch\n";
    echo "2. Login as GM to see the approval request\n";
    echo "3. GM approves the request\n";
    echo "4. Operational user can then proceed with the work\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
