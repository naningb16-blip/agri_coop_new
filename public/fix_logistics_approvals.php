<?php
// Fix Logistics Department Approval Workflow
// Adds GM approval chain for logistics module

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Logistics Approval Workflow Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if logistics approval chain exists
    $result = $conn->query("SELECT * FROM approval_chains WHERE module='logistics'");
    $exists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- Logistics approval chain: " . ($exists ? "EXISTS" : "MISSING") . "\n\n";
    
    if ($exists) {
        echo "✓ Approval chain already exists!\n\n";
        echo "Existing chains:\n";
        $result = $conn->query("SELECT * FROM approval_chains WHERE module='logistics'");
        while ($row = $result->fetch_assoc()) {
            echo "  - Step {$row['step_order']}: {$row['label']} (Role: {$row['approver_role']})\n";
        }
    } else {
        echo "Adding approval chain...\n\n";
        
        // Add GM approval chain for logistics module
        $stmt = $conn->prepare("INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?, ?, ?, ?, ?)");
        $module = 'logistics';
        $step = 1;
        $role = 'gm';
        $label = 'General Manager Approval';
        $isGm = 1;
        $stmt->bind_param('sissi', $module, $step, $role, $label, $isGm);
        $stmt->execute();
        
        echo "✓ GM approval chain added for logistics module\n\n";
    }
    
    // Check existing approval requests
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected
        FROM approval_requests
        WHERE module='logistics' AND reference_type='delivery'
    ");
    
    $stats = $result->fetch_assoc();
    
    if ($stats && $stats['total'] > 0) {
        echo "Logistics Delivery Approval Requests:\n";
        echo "- Total: {$stats['total']}\n";
        echo "- Pending: {$stats['pending']}\n";
        echo "- Approved: {$stats['approved']}\n";
        echo "- Rejected: {$stats['rejected']}\n";
    } else {
        echo "No logistics approval requests found yet.\n";
        echo "Approval requests will be created when:\n";
        echo "- New deliveries are created (both inbound and outbound)\n";
    }
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's changed:\n";
    echo "1. GM approval chain added for logistics module\n";
    echo "2. ALL deliveries (inbound and outbound) now require GM approval\n";
    echo "3. Deliveries cannot be dispatched or marked delivered without GM approval\n";
    echo "4. GM can see delivery requests in Approvals module\n";
    echo "\nNext steps:\n";
    echo "1. Create a new delivery (inbound or outbound)\n";
    echo "2. Login as GM to see the approval request\n";
    echo "3. GM approves the delivery\n";
    echo "4. Logistics user can then dispatch and mark as delivered\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
