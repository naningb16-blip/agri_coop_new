<?php
// Diagnostic script to check withdrawal status and ledger entries
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Withdrawal Diagnostic Report</h2>";
echo "<style>table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #f2f2f2; } .error { color: red; } .success { color: green; }</style>";

// 1. Check if approval chain exists for withdrawal module
echo "<h3>1. Approval Chain for Withdrawal Module</h3>";
$chains = $db->fetchAll("SELECT * FROM approval_chains WHERE module='withdrawal' ORDER BY step_order");
if (empty($chains)) {
    echo "<p class='error'>❌ NO APPROVAL CHAIN FOUND! You need to run fix_withdrawal_approval_chain.php</p>";
} else {
    echo "<p class='success'>✅ Approval chain exists</p>";
    echo "<table><tr><th>Step</th><th>Approver Role</th><th>Label</th><th>Is GM Step</th></tr>";
    foreach ($chains as $c) {
        echo "<tr><td>{$c['step_order']}</td><td>{$c['approver_role']}</td><td>{$c['label']}</td><td>" . ($c['is_gm_step'] ? 'Yes' : 'No') . "</td></tr>";
    }
    echo "</table>";
}

// 2. Check recent withdrawals
echo "<h3>2. Recent Withdrawal Requests</h3>";
$withdrawals = $db->fetchAll(
    "SELECT fw.*, f.full_name as farmer_name, 
            ar.id as approval_request_id, ar.status as approval_status, ar.current_step
     FROM farmer_withdrawals fw
     JOIN farmers f ON fw.farmer_id = f.id
     LEFT JOIN approval_requests ar ON ar.reference_type='withdrawal' AND ar.reference_id=fw.id
     ORDER BY fw.created_at DESC LIMIT 10"
);

if (empty($withdrawals)) {
    echo "<p>No withdrawals found</p>";
} else {
    echo "<table><tr><th>ID</th><th>Farmer</th><th>Amount</th><th>Withdrawal Status</th><th>Approval Request ID</th><th>Approval Status</th><th>Current Step</th></tr>";
    foreach ($withdrawals as $w) {
        echo "<tr>";
        echo "<td>{$w['id']}</td>";
        echo "<td>{$w['farmer_name']}</td>";
        echo "<td>₱" . number_format($w['amount'], 2) . "</td>";
        echo "<td>{$w['status']}</td>";
        echo "<td>" . ($w['approval_request_id'] ?? 'N/A') . "</td>";
        echo "<td>" . ($w['approval_status'] ?? 'N/A') . "</td>";
        echo "<td>" . ($w['current_step'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Check ledger entries for withdrawals
echo "<h3>3. Ledger Entries for Withdrawals</h3>";
$ledgerEntries = $db->fetchAll(
    "SELECT fl.*, f.full_name as farmer_name
     FROM farmer_ledger fl
     JOIN farmers f ON fl.farmer_id = f.id
     WHERE fl.category='withdrawal'
     ORDER BY fl.created_at DESC LIMIT 10"
);

if (empty($ledgerEntries)) {
    echo "<p class='error'>❌ NO LEDGER ENTRIES FOUND for withdrawals</p>";
} else {
    echo "<p class='success'>✅ Found " . count($ledgerEntries) . " ledger entries</p>";
    echo "<table><tr><th>ID</th><th>Farmer</th><th>Type</th><th>Amount</th><th>Running Balance</th><th>Reference</th><th>Date</th></tr>";
    foreach ($ledgerEntries as $le) {
        echo "<tr>";
        echo "<td>{$le['id']}</td>";
        echo "<td>{$le['farmer_name']}</td>";
        echo "<td>{$le['type']}</td>";
        echo "<td>₱" . number_format($le['amount'], 2) . "</td>";
        echo "<td>₱" . number_format($le['running_balance'], 2) . "</td>";
        echo "<td>{$le['reference_type']} #{$le['reference_id']}</td>";
        echo "<td>{$le['transaction_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check farmer balances
echo "<h3>4. Farmer Balances (Top 5)</h3>";
$farmers = $db->fetchAll(
    "SELECT f.id, f.full_name,
            COALESCE(SUM(CASE WHEN fl.type='credit' THEN fl.amount ELSE 0 END),0) AS total_credits,
            COALESCE(SUM(CASE WHEN fl.type='debit' THEN fl.amount ELSE 0 END),0) AS total_debits,
            COALESCE(SUM(CASE WHEN fl.type='credit' THEN fl.amount ELSE -fl.amount END),0) AS balance
     FROM farmers f
     LEFT JOIN farmer_ledger fl ON fl.farmer_id=f.id
     GROUP BY f.id
     ORDER BY balance DESC
     LIMIT 5"
);

echo "<table><tr><th>Farmer</th><th>Total Credits</th><th>Total Debits</th><th>Balance</th></tr>";
foreach ($farmers as $f) {
    echo "<tr>";
    echo "<td>{$f['full_name']}</td>";
    echo "<td>₱" . number_format($f['total_credits'], 2) . "</td>";
    echo "<td>₱" . number_format($f['total_debits'], 2) . "</td>";
    echo "<td>₱" . number_format($f['balance'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Check for orphaned withdrawals (approved but no ledger entry)
echo "<h3>5. Orphaned Withdrawals (Released but no ledger entry)</h3>";
$orphaned = $db->fetchAll(
    "SELECT fw.*, f.full_name as farmer_name
     FROM farmer_withdrawals fw
     JOIN farmers f ON fw.farmer_id = f.id
     LEFT JOIN farmer_ledger fl ON fl.reference_type='withdrawal' AND fl.reference_id=fw.id
     WHERE fw.status='released' AND fl.id IS NULL"
);

if (empty($orphaned)) {
    echo "<p class='success'>✅ No orphaned withdrawals found</p>";
} else {
    echo "<p class='error'>❌ Found " . count($orphaned) . " orphaned withdrawals (released but no ledger entry)</p>";
    echo "<table><tr><th>ID</th><th>Farmer</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
    foreach ($orphaned as $o) {
        echo "<tr>";
        echo "<td>{$o['id']}</td>";
        echo "<td>{$o['farmer_name']}</td>";
        echo "<td>₱" . number_format($o['amount'], 2) . "</td>";
        echo "<td>{$o['status']}</td>";
        echo "<td>{$o['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr><p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If approval chain is missing, run: <code>fix_withdrawal_approval_chain.php</code></li>";
echo "<li>If orphaned withdrawals exist, the fix script will create ledger entries for them</li>";
echo "<li>Test by creating a new withdrawal and having GM approve it through Approvals section</li>";
echo "</ol>";
