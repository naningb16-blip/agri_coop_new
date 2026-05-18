<?php
/**
 * Diagnostic script to check withdrawal and ledger entries
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>Withdrawal Diagnostic</h2>";
    
    // Get all withdrawals
    $withdrawals = $db->fetchAll(
        "SELECT fw.*, f.full_name as farmer_name,
                ar.id as approval_id, ar.status as approval_status
         FROM farmer_withdrawals fw
         JOIN farmers f ON fw.farmer_id = f.id
         LEFT JOIN approval_requests ar ON ar.reference_type='withdrawal' AND ar.reference_id=fw.id
         ORDER BY fw.created_at DESC
         LIMIT 10"
    );
    
    echo "<h3>Recent Withdrawals:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Farmer</th><th>Amount</th><th>Status</th><th>Approval Status</th><th>Approval ID</th></tr>";
    
    foreach ($withdrawals as $w) {
        echo "<tr>";
        echo "<td>{$w['id']}</td>";
        echo "<td>{$w['farmer_name']}</td>";
        echo "<td>₱" . number_format($w['amount'], 2) . "</td>";
        echo "<td>{$w['status']}</td>";
        echo "<td>" . ($w['approval_status'] ?? 'N/A') . "</td>";
        echo "<td>" . ($w['approval_id'] ?? 'N/A') . "</td>";
        echo "</tr>";
        
        // Check if ledger entry exists
        $ledgerEntry = $db->fetchOne(
            "SELECT * FROM farmer_ledger 
             WHERE reference_type='withdrawal' AND reference_id=?",
            [$w['id']], 'i'
        );
        
        if ($ledgerEntry) {
            echo "<tr><td colspan='6' style='background:#d4edda'>✓ Ledger entry exists: Debit ₱" . number_format($ledgerEntry['amount'], 2) . " on " . $ledgerEntry['transaction_date'] . "</td></tr>";
        } else {
            echo "<tr><td colspan='6' style='background:#f8d7da'>✗ No ledger entry found for this withdrawal</td></tr>";
        }
    }
    
    echo "</table>";
    
    echo "<h3>Approval Chains for 'withdrawal' module:</h3>";
    $chains = $db->fetchAll(
        "SELECT * FROM approval_chains WHERE module='withdrawal' ORDER BY step_order"
    );
    
    if (empty($chains)) {
        echo "<p style='color:red'>⚠️ NO APPROVAL CHAIN FOUND FOR WITHDRAWAL MODULE!</p>";
        echo "<p>This means withdrawals won't go through the approval system properly.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Step</th><th>Role</th><th>Label</th><th>Is GM Step</th></tr>";
        foreach ($chains as $c) {
            echo "<tr>";
            echo "<td>{$c['step_order']}</td>";
            echo "<td>{$c['approver_role']}</td>";
            echo "<td>{$c['label']}</td>";
            echo "<td>" . ($c['is_gm_step'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
