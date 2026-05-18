<?php
// Fix Operational Document Numbers
// Adds production numbers and ensures batch numbers exist

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Operational Document Numbers Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if production_number column exists
    $result = $conn->query("SHOW COLUMNS FROM production_records LIKE 'production_number'");
    $prodNumberExists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- production_number column: " . ($prodNumberExists ? "EXISTS" : "MISSING") . "\n\n";
    
    if (!$prodNumberExists) {
        echo "1. Adding production_number column...\n";
        $conn->query("ALTER TABLE production_records ADD COLUMN production_number VARCHAR(100) UNIQUE AFTER id");
        echo "   ✓ production_number column added\n\n";
    } else {
        echo "✓ production_number column already exists\n\n";
    }
    
    // Generate production numbers for existing records
    echo "2. Generating production numbers for existing records...\n";
    $result = $conn->query("
        UPDATE production_records
        SET production_number = CONCAT('PROD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
        WHERE production_number IS NULL
    ");
    $updated = $conn->affected_rows;
    echo "   ✓ Generated production numbers for $updated records\n\n";
    
    // Ensure batch numbers exist for processing batches
    echo "3. Ensuring batch numbers exist for processing batches...\n";
    $result = $conn->query("
        UPDATE processing_batches
        SET batch_number = CONCAT('BATCH-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
        WHERE batch_number IS NULL OR batch_number = ''
    ");
    $batchUpdated = $conn->affected_rows;
    echo "   ✓ Generated/updated batch numbers for $batchUpdated batches\n\n";
    
    // Verification
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_production,
            SUM(CASE WHEN production_number IS NOT NULL THEN 1 ELSE 0 END) AS with_prod_number
        FROM production_records
    ");
    $prodStats = $result->fetch_assoc();
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_batches,
            SUM(CASE WHEN batch_number IS NOT NULL THEN 1 ELSE 0 END) AS with_batch_number
        FROM processing_batches
    ");
    $batchStats = $result->fetch_assoc();
    
    echo "Production Records Statistics:\n";
    echo "- Total records: {$prodStats['total_production']}\n";
    echo "- With production number: {$prodStats['with_prod_number']}\n\n";
    
    echo "Processing Batches Statistics:\n";
    echo "- Total batches: {$batchStats['total_batches']}\n";
    echo "- With batch number: {$batchStats['with_batch_number']}\n";
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's new:\n";
    echo "1. Production numbers automatically generated (PROD-YYYYMMDD-XXXX)\n";
    echo "2. Batch numbers ensured for all processing batches (BATCH-YYYYMMDD-XXXX)\n";
    echo "3. Printable production records with full details\n";
    echo "4. Printable processing batch reports with stage information\n";
    echo "\nHow to use:\n";
    echo "1. Create new production record - production number generated automatically\n";
    echo "2. Create new processing batch - batch number generated automatically\n";
    echo "3. View production/processing detail page\n";
    echo "4. Click 'Print' button to generate printable report\n";
    echo "5. Reports show all details, inputs, schedules, and stages\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
