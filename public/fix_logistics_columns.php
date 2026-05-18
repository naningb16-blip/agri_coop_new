<?php
// Fix Logistics Inbound/Outbound Columns
// Adds missing delivery_type and warehouse_id columns

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Logistics Inbound/Outbound Column Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if columns exist
    $result = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'delivery_type'");
    $deliveryTypeExists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM deliveries LIKE 'warehouse_id'");
    $warehouseIdExists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- delivery_type column: " . ($deliveryTypeExists ? "EXISTS" : "MISSING") . "\n";
    echo "- warehouse_id column: " . ($warehouseIdExists ? "EXISTS" : "MISSING") . "\n\n";
    
    if ($deliveryTypeExists && $warehouseIdExists) {
        echo "✓ All columns already exist! No changes needed.\n";
        exit;
    }
    
    echo "Executing migration...\n\n";
    
    // Add delivery_type column
    if (!$deliveryTypeExists) {
        echo "1. Adding delivery_type column...\n";
        $conn->query("ALTER TABLE deliveries ADD COLUMN delivery_type ENUM('inbound', 'outbound') DEFAULT 'outbound' AFTER reference_id");
        echo "   ✓ delivery_type column added\n\n";
    }
    
    // Add warehouse_id column
    if (!$warehouseIdExists) {
        echo "2. Adding warehouse_id column...\n";
        $conn->query("ALTER TABLE deliveries ADD COLUMN warehouse_id INT NULL AFTER delivery_type");
        echo "   ✓ warehouse_id column added\n\n";
        
        // Add foreign key constraint
        echo "3. Adding foreign key constraint...\n";
        try {
            $conn->query("ALTER TABLE deliveries ADD CONSTRAINT fk_deliveries_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)");
            echo "   ✓ Foreign key constraint added\n\n";
        } catch (Exception $e) {
            echo "   ⚠ Foreign key constraint skipped (may already exist)\n\n";
        }
    }
    
    // Update existing deliveries based on reference_type
    echo "4. Updating existing deliveries...\n";
    
    // Purchase orders = Inbound
    $result = $conn->query("UPDATE deliveries SET delivery_type = 'inbound' WHERE reference_type IN ('purchase', 'purchase_order')");
    $inboundCount = $conn->affected_rows;
    echo "   ✓ Set $inboundCount deliveries to 'inbound' (purchase orders)\n";
    
    // Sales orders = Outbound
    $result = $conn->query("UPDATE deliveries SET delivery_type = 'outbound' WHERE reference_type IN ('sale', 'sales_order')");
    $outboundCount = $conn->affected_rows;
    echo "   ✓ Set $outboundCount deliveries to 'outbound' (sales orders)\n\n";
    
    // Set default warehouse for existing inbound deliveries
    echo "5. Setting default warehouse for inbound deliveries...\n";
    $result = $conn->query("UPDATE deliveries d SET warehouse_id = (SELECT id FROM warehouses LIMIT 1) WHERE delivery_type = 'inbound' AND warehouse_id IS NULL");
    $warehouseCount = $conn->affected_rows;
    echo "   ✓ Set warehouse for $warehouseCount inbound deliveries\n\n";
    
    // Create index
    echo "6. Creating index...\n";
    try {
        $conn->query("CREATE INDEX idx_deliveries_type ON deliveries(delivery_type)");
        echo "   ✓ Index created\n\n";
    } catch (Exception $e) {
        echo "   ⚠ Index skipped (may already exist)\n\n";
    }
    
    // Verification
    echo "=== VERIFICATION ===\n\n";
    $result = $conn->query("
        SELECT 
            delivery_type,
            COUNT(*) AS count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) AS in_transit,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
        FROM deliveries
        GROUP BY delivery_type
    ");
    
    echo "Delivery Statistics:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['delivery_type']}: {$row['count']} total ({$row['delivered']} delivered, {$row['in_transit']} in transit, {$row['pending']} pending)\n";
    }
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nYou can now:\n";
    echo "1. Create new deliveries with Inbound/Outbound types\n";
    echo "2. Select warehouse for inbound deliveries\n";
    echo "3. View delivery type badges in Logistics module\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
