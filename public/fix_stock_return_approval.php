<?php
/**
 * Add approval chain for stock_return module
 * Stock returns need GM approval before they can be restocked or disposed
 */

require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Adding approval chain for stock_return module...\n";
    
    // Check if stock_return approval chain already exists
    $existing = $db->fetchOne(
        "SELECT id FROM approval_chains WHERE module = 'stock_return' AND step_order = 1",
        [],
        ''
    );
    
    if ($existing) {
        echo "✓ Stock return approval chain already exists (ID: {$existing['id']})\n";
    } else {
        // Insert approval chain for stock_return - GM approval only
        $chainId = $db->insert(
            "INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES (?, ?, ?, ?, ?)",
            ['stock_return', 1, 'gm', 'GM Approval', 1],
            'sissi'
        );
        
        echo "✓ Created approval chain for stock_return (ID: $chainId)\n";
        echo "✓ Added GM as approver for stock returns\n";
    }
    
    echo "\n✅ Stock return approval workflow configured successfully!\n";
    echo "\nNow stock returns will require GM approval before they can be processed.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
