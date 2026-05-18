<?php
/**
 * System Diagnostic Script
 * Checks all department features and inventory integrations
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #444; border-radius: 5px; }
        .success { color: #4ec9b0; }
        .warning { color: #dcdcaa; }
        .error { color: #f48771; }
        .info { color: #9cdcfe; }
        h1 { color: #569cd6; }
        h2 { color: #4ec9b0; margin-top: 20px; }
        pre { background: #2d2d2d; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .badge-success { background: #0e639c; color: white; }
        .badge-error { background: #a1260d; color: white; }
        .badge-warning { background: #795e26; color: white; }
    </style>
</head>
<body>
<h1>🔍 Agricultural Cooperative ERP - System Diagnostic</h1>
<p class="info">Running comprehensive system checks...</p>

<?php

$errors = [];
$warnings = [];
$success = [];

// ============================================================
// Initialize Database Connection
// ============================================================
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../core/Database.php';
    
    $db = Database::getInstance();
    echo '<p class="success">✓ Database classes loaded</p>';
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h2>Fatal Error</h2>';
    echo '<p class="error">✗ Failed to load database: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div></body></html>';
    exit;
}

// ============================================================
// 1. DATABASE CONNECTIVITY
// ============================================================
echo '<div class="section">';
echo '<h2>1. Database Connectivity</h2>';
try {
    $result = $db->fetchOne("SELECT DATABASE() as db_name, VERSION() as version");
    echo '<p class="success">✓ Database connected: ' . htmlspecialchars($result['db_name']) . '</p>';
    echo '<p class="success">✓ MySQL version: ' . htmlspecialchars($result['version']) . '</p>';
    $success[] = 'Database connectivity';
} catch (Exception $e) {
    echo '<p class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    $errors[] = 'Database connectivity: ' . $e->getMessage();
}
echo '</div>';

// ============================================================
// 2. TABLE STRUCTURE CHECKS
// ============================================================
echo '<div class="section">';
echo '<h2>2. Table Structure</h2>';

$requiredTables = [
    'users', 'roles', 'permissions', 'role_permissions',
    'approval_requests', 'approval_steps', 'approval_chains', 'approval_audit_log',
    'products', 'warehouses', 'inventory', 'stock_movements',
    'deliveries', 'delivery_items', 'delivery_receipts',
    'production_records', 'production_inputs', 'production_schedules',
    'processing_batches', 'processing_stage_logs',
    'purchase_orders', 'purchase_order_items', 'sales_orders',
    'stock_release_requests', 'stock_returns'
];

try {
    $tables = $db->fetchAll("SHOW TABLES");
    $tableKey = array_keys($tables[0])[0];
    $existingTables = array_column($tables, $tableKey);

    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            try {
                $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM `$table`");
                echo '<p class="success">✓ ' . $table . ' <span class="badge badge-success">' . $count['cnt'] . ' records</span></p>';
            } catch (Exception $e) {
                echo '<p class="warning">⚠ ' . $table . ' exists but error counting: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="error">✗ Missing table: ' . $table . '</p>';
            $errors[] = "Missing table: $table";
        }
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking tables: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div>';

// ============================================================
// 3. LOGISTICS DEPARTMENT
// ============================================================
echo '<div class="section">';
echo '<h2>3. Logistics Department</h2>';

try {
    $deliveries = $db->fetchAll("SELECT * FROM deliveries LIMIT 5");
    echo '<p class="info">Found ' . count($deliveries) . ' deliveries (showing max 5)</p>';

    foreach ($deliveries as $delivery) {
        echo '<div style="margin-left: 20px; margin-bottom: 10px;">';
        echo '<p><strong>Delivery #' . $delivery['id'] . '</strong> - Status: <span class="badge badge-' . 
             ($delivery['status'] === 'delivered' ? 'success' : 'warning') . '">' . 
             $delivery['status'] . '</span></p>';
        
        // Check if delivery has items
        $items = $db->fetchAll("SELECT * FROM delivery_items WHERE delivery_id=?", [$delivery['id']], 'i');
        if (empty($items)) {
            echo '<p class="warning">⚠ No delivery items found</p>';
            $warnings[] = "Delivery #{$delivery['id']} has no items";
        } else {
            echo '<p class="success">✓ ' . count($items) . ' items</p>';
        }
        
        // Check inventory movements
        $movements = $db->fetchAll(
            "SELECT * FROM stock_movements WHERE reference_type='delivery' AND reference_id=?",
            [$delivery['id']], 'i'
        );
        
        if ($delivery['status'] === 'delivered' && $delivery['reference_type'] === 'purchase') {
            if (empty($movements)) {
                echo '<p class="error">✗ No stock movements (should have stock-in)</p>';
                $errors[] = "Delivery #{$delivery['id']} (purchase) delivered but no stock-in movement";
            } else {
                echo '<p class="success">✓ ' . count($movements) . ' stock movements</p>';
            }
        }
        
        if ($delivery['status'] === 'in_transit' && $delivery['reference_type'] === 'sale') {
            $outMovements = $db->fetchAll(
                "SELECT * FROM stock_movements WHERE reference_type='delivery' AND reference_id=? AND type='out'",
                [$delivery['id']], 'i'
            );
            if (empty($outMovements)) {
                echo '<p class="error">✗ No stock-out movements (should deduct on in_transit)</p>';
                $errors[] = "Delivery #{$delivery['id']} (sale) in_transit but no stock-out movement";
            } else {
                echo '<p class="success">✓ Stock deducted on in_transit</p>';
            }
        }
        
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking logistics: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div>';

// ============================================================
// 4. PRODUCTION DEPARTMENT
// ============================================================
echo '<div class="section">';
echo '<h2>4. Production Department</h2>';

try {
    $productions = $db->fetchAll("SELECT * FROM production_records LIMIT 5");
    echo '<p class="info">Found ' . count($productions) . ' production records (showing max 5)</p>';

    foreach ($productions as $prod) {
        echo '<div style="margin-left: 20px; margin-bottom: 10px;">';
        echo '<p><strong>Production #' . $prod['id'] . '</strong> - Status: <span class="badge badge-' . 
             ($prod['status'] === 'completed' ? 'success' : 'warning') . '">' . 
             $prod['status'] . '</span></p>';
        
        // Check planting stock-out
        if (in_array($prod['status'], ['planted', 'growing', 'harvested', 'completed'])) {
            $plantMovements = $db->fetchAll(
                "SELECT * FROM stock_movements WHERE reference_type='production' AND reference_id=? AND type='out'",
                [$prod['id']], 'i'
            );
            if (empty($plantMovements)) {
                echo '<p class="warning">⚠ No seed stock-out movement (planting)</p>';
                $warnings[] = "Production #{$prod['id']} planted but no seed deduction";
            } else {
                echo '<p class="success">✓ Seeds deducted: ' . count($plantMovements) . ' movements</p>';
            }
        }
        
        // Check harvest stock-in
        if (in_array($prod['status'], ['harvested', 'completed']) && $prod['actual_yield'] > 0) {
            $harvestMovements = $db->fetchAll(
                "SELECT * FROM stock_movements WHERE reference_type='production' AND reference_id=? AND type='in'",
                [$prod['id']], 'i'
            );
            if (empty($harvestMovements)) {
                echo '<p class="error">✗ No harvest stock-in movement</p>';
                $errors[] = "Production #{$prod['id']} harvested but no stock-in movement";
            } else {
                echo '<p class="success">✓ Harvest added to inventory: ' . 
                     number_format($harvestMovements[0]['quantity'], 2) . '</p>';
            }
        }
        
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking production: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div>';

// ============================================================
// 5. PROCESSING DEPARTMENT
// ============================================================
echo '<div class="section">';
echo '<h2>5. Processing Department</h2>';

try {
    $batches = $db->fetchAll("SELECT * FROM processing_batches LIMIT 5");
    echo '<p class="info">Found ' . count($batches) . ' processing batches (showing max 5)</p>';

    foreach ($batches as $batch) {
        echo '<div style="margin-left: 20px; margin-bottom: 10px;">';
        echo '<p><strong>Batch ' . htmlspecialchars($batch['batch_number']) . '</strong> - Status: <span class="badge badge-' . 
             ($batch['status'] === 'completed' ? 'success' : 'warning') . '">' . 
             $batch['status'] . '</span></p>';
        
        // Check approval
        $approval = $db->fetchOne(
            "SELECT * FROM approval_requests WHERE reference_type='processing_batch' AND reference_id=?",
            [$batch['id']], 'i'
        );
        if (!$approval) {
            echo '<p class="warning">⚠ No approval request</p>';
            $warnings[] = "Batch {$batch['batch_number']} has no approval request";
        } else {
            echo '<p class="success">✓ Approval: ' . $approval['status'] . '</p>';
        }
        
        // Check input stock deduction
        if ($batch['input_warehouse_id']) {
            $inputMovements = $db->fetchAll(
                "SELECT * FROM stock_movements WHERE reference_type='processing_batch' AND reference_id=? AND type='out'",
                [$batch['id']], 'i'
            );
            if (empty($inputMovements)) {
                echo '<p class="error">✗ No input stock deduction</p>';
                $errors[] = "Batch {$batch['batch_number']} has no input stock movement";
            } else {
                echo '<p class="success">✓ Input deducted: ' . number_format($inputMovements[0]['quantity'], 2) . '</p>';
            }
        }
        
        // Check output stock addition
        if ($batch['status'] === 'completed' && $batch['output_warehouse_id'] && $batch['output_quantity'] > 0) {
            $outputMovements = $db->fetchAll(
                "SELECT * FROM stock_movements WHERE reference_type='processing_batch' AND reference_id=? AND type='in'",
                [$batch['id']], 'i'
            );
            if (empty($outputMovements)) {
                echo '<p class="error">✗ No output stock addition</p>';
                $errors[] = "Batch {$batch['batch_number']} completed but no output stock movement";
            } else {
                echo '<p class="success">✓ Output added: ' . number_format($outputMovements[0]['quantity'], 2) . '</p>';
            }
        }
        
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking processing: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
echo '</div>';

// ============================================================
// 6. INVENTORY INTEGRITY
// ============================================================
echo '<div class="section">';
echo '<h2>6. Inventory Integrity</h2>';

try {
    // Check for negative stock
    $negativeStock = $db->fetchAll(
        "SELECT i.*, p.name as product_name, w.name as warehouse_name 
         FROM inventory i 
         JOIN products p ON i.product_id = p.id 
         JOIN warehouses w ON i.warehouse_id = w.id 
         WHERE i.quantity < 0"
    );

    if (!empty($negativeStock)) {
        echo '<p class="error">✗ Found ' . count($negativeStock) . ' negative stock entries:</p>';
        foreach ($negativeStock as $stock) {
            echo '<p class="error" style="margin-left: 20px;">- ' . 
                 htmlspecialchars($stock['product_name']) . ' at ' . 
                 htmlspecialchars($stock['warehouse_name']) . ': ' . 
                 number_format($stock['quantity'], 2) . '</p>';
            $errors[] = "Negative stock: {$stock['product_name']} at {$stock['warehouse_name']}";
        }
    } else {
        echo '<p class="success">✓ No negative stock found</p>';
        $success[] = 'Inventory integrity';
    }

    // Check stock movements by type
    $orphanMovements = $db->fetchAll(
        "SELECT COUNT(*) as cnt, reference_type 
         FROM stock_movements 
         GROUP BY reference_type"
    );
    echo '<p class="info">Stock movements by type:</p>';
    foreach ($orphanMovements as $mov) {
        echo '<p style="margin-left: 20px;">- ' . $mov['reference_type'] . ': ' . $mov['cnt'] . '</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking inventory: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// ============================================================
// SUMMARY
// ============================================================
echo '<div class="section">';
echo '<h2>📊 Summary</h2>';
echo '<p class="success">✓ Passed: ' . count($success) . '</p>';
echo '<p class="warning">⚠ Warnings: ' . count($warnings) . '</p>';
echo '<p class="error">✗ Errors: ' . count($errors) . '</p>';

if (empty($errors)) {
    echo '<p class="success" style="font-size: 18px; margin-top: 20px;">🎉 All critical checks passed!</p>';
} else {
    echo '<p class="error" style="font-size: 18px; margin-top: 20px;">⚠️ Please review and fix the errors above.</p>';
}

echo '<p class="info" style="margin-top: 20px;">Diagnostic completed at: ' . date('Y-m-d H:i:s') . '</p>';
echo '</div>';

?>
</body>
</html>
