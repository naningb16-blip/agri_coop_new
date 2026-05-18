<?php
/**
 * Fix Inventory Integration for Existing Records
 * This script retroactively creates stock movements for existing records
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/models/InventoryModel.php';

$db = Database::getInstance();
$inventoryModel = new InventoryModel();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Inventory Integration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #444; border-radius: 5px; }
        .success { color: #4ec9b0; }
        .warning { color: #dcdcaa; }
        .error { color: #f48771; }
        .info { color: #9cdcfe; }
        h1 { color: #569cd6; }
        h2 { color: #4ec9b0; margin-top: 20px; }
        .btn { padding: 10px 20px; background: #0e639c; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        .btn:hover { background: #1177bb; }
        .btn-danger { background: #a1260d; }
        .btn-danger:hover { background: #c42a1a; }
    </style>
</head>
<body>
<h1>🔧 Fix Inventory Integration</h1>
<p class="warning">⚠️ This script will create stock movements for existing records that are missing them.</p>
<p class="info">Review the analysis below before running the fix.</p>

<?php

$fixed = 0;
$skipped = 0;
$errors = [];

// Check if fix should run
$runFix = isset($_GET['run']) && $_GET['run'] === 'yes';

if (!$runFix) {
    echo '<div class="section">';
    echo '<h2>Analysis Mode</h2>';
    echo '<p class="info">Analyzing existing records...</p>';
}

// ============================================================
// 1. FIX DELIVERIES
// ============================================================
echo '<div class="section">';
echo '<h2>1. Deliveries</h2>';

// Find delivered purchases without stock movements
$deliveredPurchases = $db->fetchAll(
    "SELECT d.* FROM deliveries d
     LEFT JOIN stock_movements sm ON sm.reference_type='delivery' AND sm.reference_id=d.id AND sm.type='in'
     WHERE d.status='delivered' AND d.reference_type='purchase' AND sm.id IS NULL"
);

echo '<p class="info">Found ' . count($deliveredPurchases) . ' delivered purchases without stock-in movements</p>';

if ($runFix && !empty($deliveredPurchases)) {
    $warehouses = $inventoryModel->getWarehouses();
    $defaultWarehouse = $warehouses[0]['id'] ?? null;
    
    if (!$defaultWarehouse) {
        echo '<p class="error">✗ No warehouse found. Please create a warehouse first.</p>';
    } else {
        foreach ($deliveredPurchases as $delivery) {
            try {
                $items = $db->fetchAll(
                    "SELECT * FROM delivery_items WHERE delivery_id=?",
                    [$delivery['id']], 'i'
                );
                
                foreach ($items as $item) {
                    $inventoryModel->addMovement([
                        'product_id'     => $item['product_id'],
                        'warehouse_id'   => $defaultWarehouse,
                        'type'           => 'in',
                        'quantity'       => $item['quantity'],
                        'reference_type' => 'delivery',
                        'reference_id'   => $delivery['id'],
                        'notes'          => 'Retroactive stock-in from delivery #' . $delivery['id'],
                    ]);
                }
                echo '<p class="success">✓ Fixed delivery #' . $delivery['id'] . ' (' . count($items) . ' items)</p>';
                $fixed++;
            } catch (Exception $e) {
                echo '<p class="error">✗ Error fixing delivery #' . $delivery['id'] . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                $errors[] = "Delivery #{$delivery['id']}: " . $e->getMessage();
            }
        }
    }
}

// Find in_transit sales without stock movements
$inTransitSales = $db->fetchAll(
    "SELECT d.* FROM deliveries d
     LEFT JOIN stock_movements sm ON sm.reference_type='delivery' AND sm.reference_id=d.id AND sm.type='out'
     WHERE d.status IN ('in_transit', 'delivered') AND d.reference_type='sale' AND sm.id IS NULL"
);

echo '<p class="info">Found ' . count($inTransitSales) . ' sales deliveries without stock-out movements</p>';

if ($runFix && !empty($inTransitSales)) {
    $warehouses = $inventoryModel->getWarehouses();
    $defaultWarehouse = $warehouses[0]['id'] ?? null;
    
    if ($defaultWarehouse) {
        foreach ($inTransitSales as $delivery) {
            try {
                $items = $db->fetchAll(
                    "SELECT * FROM delivery_items WHERE delivery_id=?",
                    [$delivery['id']], 'i'
                );
                
                foreach ($items as $item) {
                    $inventoryModel->addMovement([
                        'product_id'     => $item['product_id'],
                        'warehouse_id'   => $defaultWarehouse,
                        'type'           => 'out',
                        'quantity'       => $item['quantity'],
                        'reference_type' => 'delivery',
                        'reference_id'   => $delivery['id'],
                        'notes'          => 'Retroactive stock-out from delivery #' . $delivery['id'],
                    ]);
                }
                echo '<p class="success">✓ Fixed delivery #' . $delivery['id'] . ' (' . count($items) . ' items)</p>';
                $fixed++;
            } catch (Exception $e) {
                echo '<p class="error">✗ Error fixing delivery #' . $delivery['id'] . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                $errors[] = "Delivery #{$delivery['id']}: " . $e->getMessage();
            }
        }
    }
}

echo '</div>';

// ============================================================
// 2. FIX PRODUCTION
// ============================================================
echo '<div class="section">';
echo '<h2>2. Production Records</h2>';

// Find harvested production without stock movements
$harvestedProduction = $db->fetchAll(
    "SELECT pr.* FROM production_records pr
     LEFT JOIN stock_movements sm ON sm.reference_type='production' AND sm.reference_id=pr.id AND sm.type='in'
     WHERE pr.status IN ('harvested', 'completed') AND pr.actual_yield > 0 AND sm.id IS NULL"
);

echo '<p class="info">Found ' . count($harvestedProduction) . ' harvested productions without stock-in movements</p>';

if ($runFix && !empty($harvestedProduction)) {
    $warehouses = $inventoryModel->getWarehouses();
    $defaultWarehouse = $warehouses[0]['id'] ?? null;
    
    if ($defaultWarehouse) {
        foreach ($harvestedProduction as $prod) {
            try {
                $inventoryModel->addMovement([
                    'product_id'     => $prod['product_id'],
                    'warehouse_id'   => $defaultWarehouse,
                    'type'           => 'in',
                    'quantity'       => $prod['actual_yield'],
                    'reference_type' => 'production',
                    'reference_id'   => $prod['id'],
                    'notes'          => 'Retroactive harvest stock-in from production #' . $prod['id'],
                ]);
                echo '<p class="success">✓ Fixed production #' . $prod['id'] . ' (yield: ' . number_format($prod['actual_yield'], 2) . ')</p>';
                $fixed++;
            } catch (Exception $e) {
                echo '<p class="error">✗ Error fixing production #' . $prod['id'] . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                $errors[] = "Production #{$prod['id']}: " . $e->getMessage();
            }
        }
    }
}

echo '</div>';

// ============================================================
// 3. FIX PROCESSING
// ============================================================
echo '<div class="section">';
echo '<h2>3. Processing Batches</h2>';

// Find batches without input stock movements
$batchesNoInput = $db->fetchAll(
    "SELECT pb.* FROM processing_batches pb
     LEFT JOIN stock_movements sm ON sm.reference_type='processing_batch' AND sm.reference_id=pb.id AND sm.type='out'
     WHERE pb.input_warehouse_id IS NOT NULL AND pb.status != 'pending' AND sm.id IS NULL"
);

echo '<p class="info">Found ' . count($batchesNoInput) . ' batches without input stock deduction</p>';

if ($runFix && !empty($batchesNoInput)) {
    foreach ($batchesNoInput as $batch) {
        try {
            $inventoryModel->addMovement([
                'product_id'     => $batch['product_id'],
                'warehouse_id'   => $batch['input_warehouse_id'],
                'type'           => 'out',
                'quantity'       => $batch['input_quantity'],
                'reference_type' => 'processing_batch',
                'reference_id'   => $batch['id'],
                'notes'          => 'Retroactive input deduction for batch ' . $batch['batch_number'],
            ]);
            echo '<p class="success">✓ Fixed batch ' . htmlspecialchars($batch['batch_number']) . ' (input: ' . number_format($batch['input_quantity'], 2) . ')</p>';
            $fixed++;
        } catch (Exception $e) {
            echo '<p class="error">✗ Error fixing batch ' . htmlspecialchars($batch['batch_number']) . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
            $errors[] = "Batch {$batch['batch_number']}: " . $e->getMessage();
        }
    }
}

// Find completed batches without output stock movements
$batchesNoOutput = $db->fetchAll(
    "SELECT pb.* FROM processing_batches pb
     LEFT JOIN stock_movements sm ON sm.reference_type='processing_batch' AND sm.reference_id=pb.id AND sm.type='in'
     WHERE pb.status='completed' AND pb.output_warehouse_id IS NOT NULL AND pb.output_quantity > 0 AND sm.id IS NULL"
);

echo '<p class="info">Found ' . count($batchesNoOutput) . ' completed batches without output stock addition</p>';

if ($runFix && !empty($batchesNoOutput)) {
    foreach ($batchesNoOutput as $batch) {
        try {
            $inventoryModel->addMovement([
                'product_id'     => $batch['product_id'],
                'warehouse_id'   => $batch['output_warehouse_id'],
                'type'           => 'in',
                'quantity'       => $batch['output_quantity'],
                'reference_type' => 'processing_batch',
                'reference_id'   => $batch['id'],
                'notes'          => 'Retroactive output addition for batch ' . $batch['batch_number'],
            ]);
            echo '<p class="success">✓ Fixed batch ' . htmlspecialchars($batch['batch_number']) . ' (output: ' . number_format($batch['output_quantity'], 2) . ')</p>';
            $fixed++;
        } catch (Exception $e) {
            echo '<p class="error">✗ Error fixing batch ' . htmlspecialchars($batch['batch_number']) . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
            $errors[] = "Batch {$batch['batch_number']}: " . $e->getMessage();
        }
    }
}

echo '</div>';

// ============================================================
// SUMMARY
// ============================================================
echo '<div class="section">';
echo '<h2>📊 Summary</h2>';

if (!$runFix) {
    $totalIssues = count($deliveredPurchases) + count($inTransitSales) + count($harvestedProduction) + count($batchesNoInput) + count($batchesNoOutput);
    echo '<p class="warning">Found ' . $totalIssues . ' records that need fixing</p>';
    echo '<p class="info">Click the button below to fix these issues:</p>';
    echo '<form method="get">';
    echo '<button type="submit" name="run" value="yes" class="btn">Run Fix</button>';
    echo '<a href="diagnostic.php" class="btn">Back to Diagnostic</a>';
    echo '</form>';
} else {
    echo '<p class="success">✓ Fixed: ' . $fixed . ' records</p>';
    echo '<p class="error">✗ Errors: ' . count($errors) . '</p>';
    
    if (empty($errors)) {
        echo '<p class="success" style="font-size: 18px; margin-top: 20px;">🎉 All issues fixed successfully!</p>';
    } else {
        echo '<p class="error" style="font-size: 18px; margin-top: 20px;">⚠️ Some errors occurred. Please review above.</p>';
    }
    
    echo '<p style="margin-top: 20px;">';
    echo '<a href="diagnostic.php" class="btn">Run Diagnostic Again</a>';
    echo '<a href="fix_inventory_integration.php" class="btn">Analyze Again</a>';
    echo '</p>';
}

echo '<p class="info" style="margin-top: 20px;">Completed at: ' . date('Y-m-d H:i:s') . '</p>';
echo '</div>';

?>
</body>
</html>
