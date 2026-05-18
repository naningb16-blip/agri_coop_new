<?php
// Fix Logistics Delivery Receipt Enhancement
// Adds DR numbers, unit costs, and total amounts

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Logistics Delivery Receipt Enhancement Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if columns exist
    $result = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'dr_number'");
    $drNumberExists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM delivery_items LIKE 'unit_cost'");
    $unitCostExists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM delivery_items LIKE 'total_amount'");
    $totalAmountExists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- dr_number column (deliveries): " . ($drNumberExists ? "EXISTS" : "MISSING") . "\n";
    echo "- unit_cost column (delivery_items): " . ($unitCostExists ? "EXISTS" : "MISSING") . "\n";
    echo "- total_amount column (delivery_items): " . ($totalAmountExists ? "EXISTS" : "MISSING") . "\n\n";
    
    if ($drNumberExists && $unitCostExists && $totalAmountExists) {
        echo "✓ All columns already exist!\n\n";
    } else {
        echo "Adding missing columns...\n\n";
        
        if (!$drNumberExists) {
            echo "1. Adding dr_number column to deliveries...\n";
            $conn->query("ALTER TABLE deliveries ADD COLUMN dr_number VARCHAR(100) UNIQUE AFTER id");
            echo "   ✓ dr_number column added\n\n";
        }
        
        if (!$unitCostExists) {
            echo "2. Adding unit_cost column to delivery_items...\n";
            $conn->query("ALTER TABLE delivery_items ADD COLUMN unit_cost DECIMAL(12,2) DEFAULT 0 AFTER quantity");
            echo "   ✓ unit_cost column added\n\n";
        }
        
        if (!$totalAmountExists) {
            echo "3. Adding total_amount column to delivery_items...\n";
            $conn->query("ALTER TABLE delivery_items ADD COLUMN total_amount DECIMAL(12,2) DEFAULT 0 AFTER unit_cost");
            echo "   ✓ total_amount column added\n\n";
        }
    }
    
    // Generate DR numbers for existing deliveries
    echo "4. Generating DR numbers for existing deliveries...\n";
    $result = $conn->query("
        UPDATE deliveries
        SET dr_number = CONCAT('DR-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
        WHERE dr_number IS NULL
    ");
    $updated = $conn->affected_rows;
    echo "   ✓ Generated DR numbers for $updated deliveries\n\n";
    
    // Calculate total_amount for existing items
    echo "5. Calculating total amounts for existing items...\n";
    $result = $conn->query("
        UPDATE delivery_items
        SET total_amount = quantity * unit_cost
        WHERE total_amount = 0 AND unit_cost > 0
    ");
    $calculated = $conn->affected_rows;
    echo "   ✓ Calculated total amounts for $calculated items\n\n";
    
    // Verification
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_deliveries,
            SUM(CASE WHEN dr_number IS NOT NULL THEN 1 ELSE 0 END) AS with_dr_number
        FROM deliveries
    ");
    $deliveryStats = $result->fetch_assoc();
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_items,
            SUM(CASE WHEN unit_cost > 0 THEN 1 ELSE 0 END) AS with_unit_cost,
            SUM(CASE WHEN total_amount > 0 THEN 1 ELSE 0 END) AS with_total_amount
        FROM delivery_items
    ");
    $itemStats = $result->fetch_assoc();
    
    echo "Deliveries Statistics:\n";
    echo "- Total deliveries: {$deliveryStats['total_deliveries']}\n";
    echo "- With DR number: {$deliveryStats['with_dr_number']}\n\n";
    
    echo "Delivery Items Statistics:\n";
    echo "- Total items: {$itemStats['total_items']}\n";
    echo "- With unit cost: {$itemStats['with_unit_cost']}\n";
    echo "- With total amount: {$itemStats['with_total_amount']}\n";
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's new:\n";
    echo "1. DR (Delivery Receipt) numbers automatically generated (DR-YYYYMMDD-XXXX)\n";
    echo "2. Unit cost field added to delivery items\n";
    echo "3. Total amount calculated automatically (quantity × unit cost)\n";
    echo "4. Enhanced delivery receipt shows costs and totals\n";
    echo "5. Grand total displayed on delivery receipts\n";
    echo "\nHow to use:\n";
    echo "1. Create new delivery with items\n";
    echo "2. Enter unit cost for each item\n";
    echo "3. Total amount calculated automatically\n";
    echo "4. DR number generated automatically\n";
    echo "5. Print delivery receipt with costs and totals\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
