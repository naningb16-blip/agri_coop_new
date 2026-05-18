<?php
// Fix Sales Invoice Feature
// Adds invoice numbers for cash sales

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Sales Invoice Feature Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if invoice_number column exists
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'invoice_number'");
    $invoiceNumberExists = $result->num_rows > 0;
    
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'invoice_date'");
    $invoiceDateExists = $result->num_rows > 0;
    
    echo "Current status:\n";
    echo "- invoice_number column: " . ($invoiceNumberExists ? "EXISTS" : "MISSING") . "\n";
    echo "- invoice_date column: " . ($invoiceDateExists ? "EXISTS" : "MISSING") . "\n\n";
    
    if ($invoiceNumberExists && $invoiceDateExists) {
        echo "✓ All columns already exist!\n\n";
    } else {
        echo "Adding columns...\n\n";
        
        if (!$invoiceNumberExists) {
            echo "1. Adding invoice_number column...\n";
            $conn->query("ALTER TABLE sales_orders ADD COLUMN invoice_number VARCHAR(100) UNIQUE AFTER so_number");
            echo "   ✓ invoice_number column added\n\n";
        }
        
        if (!$invoiceDateExists) {
            echo "2. Adding invoice_date column...\n";
            $conn->query("ALTER TABLE sales_orders ADD COLUMN invoice_date DATETIME AFTER invoice_number");
            echo "   ✓ invoice_date column added\n\n";
        }
    }
    
    // Generate invoice numbers for existing cash sales
    echo "3. Generating invoice numbers for existing cash sales...\n";
    $result = $conn->query("
        UPDATE sales_orders so
        LEFT JOIN receipts r ON r.reference_type = 'sale' AND r.reference_id = so.id
        SET so.invoice_number = CONCAT('INV-', DATE_FORMAT(so.order_date, '%Y%m%d'), '-', LPAD(so.id, 4, '0')),
            so.invoice_date = so.created_at
        WHERE so.invoice_number IS NULL 
          AND r.id IS NOT NULL
    ");
    $updated = $conn->affected_rows;
    echo "   ✓ Generated invoice numbers for $updated cash sales\n\n";
    
    // Verification
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(CASE WHEN invoice_number IS NOT NULL THEN 1 ELSE 0 END) AS with_invoice,
            SUM(CASE WHEN invoice_number IS NULL THEN 1 ELSE 0 END) AS without_invoice
        FROM sales_orders
    ");
    
    $stats = $result->fetch_assoc();
    
    echo "Sales Orders Statistics:\n";
    echo "- Total orders: {$stats['total_orders']}\n";
    echo "- With invoice: {$stats['with_invoice']}\n";
    echo "- Without invoice: {$stats['without_invoice']}\n";
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's new:\n";
    echo "1. Invoice numbers automatically generated for cash sales\n";
    echo "2. Invoice date recorded when order is created\n";
    echo "3. Printable invoice available for cash sales\n";
    echo "4. Invoice number displayed in sales order detail\n";
    echo "\nHow to use:\n";
    echo "1. Create a new sales order with payment type 'Cash Receipt'\n";
    echo "2. Invoice number will be automatically generated (INV-YYYYMMDD-XXXX)\n";
    echo "3. Click 'Print Invoice' button in sales order detail to print\n";
    echo "4. Invoice shows customer info, items, prices, and totals\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
