<?php
/**
 * Fix withdrawal approval chain
 * Ensures withdrawal module has proper approval chain configured
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>Withdrawal Approval Chain Fix</h2>";
    
    // Check if withdrawal approval chain exists
    $existing = $db->fetchAll(
        "SELECT * FROM approval_chains WHERE module = 'withdrawal' ORDER BY step_order",
        [],
        ''
    );
    
    if (empty($existing)) {
        echo "<p style='color:orange'>⚠️ No approval chain found for withdrawal module. Creating...</p>";
        
        // Create approval chain: Finance Manager → GM
        $db->insert(
            "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?, ?, ?, ?, ?)",
            ['withdrawal', 1, 'manager', 'Finance Manager', 0],
            'sissi'
        );
        echo "<p style='color:green'>✓ Added Finance Manager approval step</p>";
        
        $db->insert(
            "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?, ?, ?, ?, ?)",
            ['withdrawal', 2, 'gm', 'General Manager', 1],
            'sissi'
        );
        echo "<p style='color:green'>✓ Added GM approval step</p>";
        
    } else {
        echo "<p style='color:green'>✓ Approval chain already exists:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Step</th><th>Role</th><th>Label</th><th>Is GM Step</th></tr>";
        foreach ($existing as $chain) {
            echo "<tr>";
            echo "<td>{$chain['step_order']}</td>";
            echo "<td>{$chain['approver_role']}</td>";
            echo "<td>{$chain['label']}</td>";
            echo "<td>" . ($chain['is_gm_step'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for withdrawals without approval requests
    echo "<h3>Checking Withdrawals...</h3>";
    
    $withdrawalsWithoutApproval = $db->fetchAll(
        "SELECT fw.*, f.full_name as farmer_name
         FROM farmer_withdrawals fw
         JOIN farmers f ON fw.farmer_id = f.id
         LEFT JOIN approval_requests ar ON ar.reference_type='withdrawal' AND ar.reference_id=fw.id
         WHERE ar.id IS NULL AND fw.status != 'rejected'
         ORDER BY fw.created_at DESC"
    );
    
    if (!empty($withdrawalsWithoutApproval)) {
        echo "<p style='color:orange'>⚠️ Found " . count($withdrawalsWithoutApproval) . " withdrawal(s) without approval requests</p>";
        echo "<p>These withdrawals were created before the approval system was integrated.</p>";
        echo "<p>Creating approval requests for them now...</p>";
        
        foreach ($withdrawalsWithoutApproval as $w) {
            // Create approval request
            $requestId = $db->insert(
                "INSERT INTO approval_requests (module, reference_type, reference_id, title, description, requested_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    'withdrawal',
                    'withdrawal',
                    $w['id'],
                    "Withdrawal: {$w['farmer_name']} ₱" . number_format($w['amount'], 2),
                    $w['reason'] ?: 'No reason provided',
                    $w['requested_by']
                ],
                'ssissi'
            );
            
            // Get approval chain steps
            $steps = $db->fetchAll(
                "SELECT * FROM approval_chains WHERE module='withdrawal' ORDER BY step_order",
                [],
                ''
            );
            
            // Create approval steps
            foreach ($steps as $step) {
                $db->insert(
                    "INSERT INTO approval_steps (request_id, step_order, approver_role, label, status)
                     VALUES (?, ?, ?, ?, 'pending')",
                    [$requestId, $step['step_order'], $step['approver_role'], $step['label']],
                    'iiss'
                );
            }
            
            // Update approval request with first step
            $db->query(
                "UPDATE approval_requests SET current_step = 1 WHERE id = ?",
                [$requestId],
                'i'
            );
            
            echo "<p style='color:green'>✓ Created approval request for withdrawal #{$w['id']} ({$w['farmer_name']})</p>";
        }
    } else {
        echo "<p style='color:green'>✓ All withdrawals have approval requests</p>";
    }
    
    // Check for approved withdrawals without ledger entries
    echo "<h3>Checking Ledger Entries...</h3>";
    
    $approvedWithdrawals = $db->fetchAll(
        "SELECT fw.*, f.full_name as farmer_name, ar.status as approval_status
         FROM farmer_withdrawals fw
         JOIN farmers f ON fw.farmer_id = f.id
         LEFT JOIN approval_requests ar ON ar.reference_type='withdrawal' AND ar.reference_id=fw.id
         WHERE ar.status = 'approved' AND fw.status IN ('approved', 'released')
         ORDER BY fw.created_at DESC"
    );
    
    $fixed = 0;
    foreach ($approvedWithdrawals as $w) {
        // Check if ledger entry exists
        $ledgerEntry = $db->fetchOne(
            "SELECT id FROM farmer_ledger WHERE reference_type='withdrawal' AND reference_id=?",
            [$w['id']], 'i'
        );
        
        if (!$ledgerEntry) {
            echo "<p style='color:orange'>⚠️ Withdrawal #{$w['id']} is approved but has no ledger entry. Creating...</p>";
            
            // Get current balance
            $balance = $db->fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN type='credit' THEN amount ELSE -amount END),0) AS bal
                 FROM farmer_ledger WHERE farmer_id=?",
                [$w['farmer_id']], 'i'
            );
            
            $currentBalance = (float)($balance['bal'] ?? 0);
            $withdrawalAmount = (float)$w['amount'];
            $newBalance = $currentBalance - $withdrawalAmount;
            
            // Create ledger entry
            $db->insert(
                "INSERT INTO farmer_ledger
                    (farmer_id, type, category, reference_type, reference_id, amount, running_balance, description, transaction_date, recorded_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                [
                    $w['farmer_id'],
                    'debit',
                    'withdrawal',
                    'withdrawal',
                    $w['id'],
                    $withdrawalAmount,
                    $newBalance,
                    "Withdrawal: " . ($w['reason'] ?? 'No reason provided'),
                    date('Y-m-d'),
                    $w['approved_by'] ?? 1
                ],
                'isssiddssi'
            );
            
            // Update withdrawal status to released
            $db->query(
                "UPDATE farmer_withdrawals SET status='released', released_at=NOW() WHERE id=?",
                [$w['id']], 'i'
            );
            
            echo "<p style='color:green'>✓ Created ledger entry and marked withdrawal as released</p>";
            $fixed++;
        }
    }
    
    if ($fixed > 0) {
        echo "<p style='color:green'><strong>✓ Fixed {$fixed} withdrawal(s)</strong></p>";
    } else {
        echo "<p style='color:green'>✓ All approved withdrawals have ledger entries</p>";
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p>✅ Withdrawal approval chain is configured</p>";
    echo "<p>✅ All withdrawals have approval requests</p>";
    echo "<p>✅ All approved withdrawals have ledger entries</p>";
    echo "<p><strong>The withdrawal system is now fully functional!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
