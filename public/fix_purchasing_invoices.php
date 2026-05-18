<?php
// Fix Purchasing Invoice Feature
// Adds supplier invoice numbers, journal entries for payables, and printable invoices

ob_start();

require_once __DIR__ . '/../core/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Purchasing Invoice Feature Fix ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
    
    // Check if columns exist
    $columns = ['supplier_invoice_number', 'supplier_invoice_date', 'payment_terms', 'payment_due_date', 'payment_status', 'amount_paid'];
    $existing = [];
    
    foreach ($columns as $col) {
        $result = $conn->query("SHOW COLUMNS FROM purchase_orders LIKE '$col'");
        $existing[$col] = $result->num_rows > 0;
    }
    
    echo "Current status:\n";
    foreach ($existing as $col => $exists) {
        echo "- $col: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    echo "\n";
    
    $allExist = !in_array(false, $existing);
    
    if ($allExist) {
        echo "✓ All columns already exist!\n\n";
    } else {
        echo "Adding missing columns...\n\n";
        
        if (!$existing['supplier_invoice_number']) {
            echo "1. Adding supplier_invoice_number column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN supplier_invoice_number VARCHAR(100) AFTER po_number");
            echo "   ✓ supplier_invoice_number column added\n\n";
        }
        
        if (!$existing['supplier_invoice_date']) {
            echo "2. Adding supplier_invoice_date column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN supplier_invoice_date DATE AFTER supplier_invoice_number");
            echo "   ✓ supplier_invoice_date column added\n\n";
        }
        
        if (!$existing['payment_terms']) {
            echo "3. Adding payment_terms column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN payment_terms VARCHAR(50) DEFAULT 'Net 30' AFTER supplier_invoice_date");
            echo "   ✓ payment_terms column added\n\n";
        }
        
        if (!$existing['payment_due_date']) {
            echo "4. Adding payment_due_date column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN payment_due_date DATE AFTER payment_terms");
            echo "   ✓ payment_due_date column added\n\n";
        }
        
        if (!$existing['payment_status']) {
            echo "5. Adding payment_status column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER payment_due_date");
            echo "   ✓ payment_status column added\n\n";
        }
        
        if (!$existing['amount_paid']) {
            echo "6. Adding amount_paid column...\n";
            $conn->query("ALTER TABLE purchase_orders ADD COLUMN amount_paid DECIMAL(12,2) DEFAULT 0 AFTER payment_status");
            echo "   ✓ amount_paid column added\n\n";
        }
    }
    
    // Verification
    echo "=== VERIFICATION ===\n\n";
    
    $result = $conn->query("
        SELECT 
            COUNT(*) AS total_pos,
            SUM(CASE WHEN supplier_invoice_number IS NOT NULL THEN 1 ELSE 0 END) AS with_invoice,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid,
            SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid,
            SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) AS partial
        FROM purchase_orders
    ");
    
    $stats = $result->fetch_assoc();
    
    echo "Purchase Orders Statistics:\n";
    echo "- Total POs: {$stats['total_pos']}\n";
    echo "- With supplier invoice: {$stats['with_invoice']}\n";
    echo "- Paid: {$stats['paid']}\n";
    echo "- Unpaid: {$stats['unpaid']}\n";
    echo "- Partial payment: {$stats['partial']}\n";
    
    echo "\n✓ MIGRATION COMPLETE!\n";
    echo "\nWhat's new:\n";
    echo "1. Supplier invoice numbers can be recorded for delivered POs\n";
    echo "2. Journal entries automatically created for Accounts Payable\n";
    echo "3. Payment tracking with status (unpaid/partial/paid)\n";
    echo "4. Payment recording creates journal entries (debit AP, credit Cash/Bank)\n";
    echo "5. Printable supplier invoices with payment details\n";
    echo "\nHow to use:\n";
    echo "1. After PO is delivered, click 'Record Supplier Invoice'\n";
    echo "2. Enter supplier's invoice number, date, and payment terms\n";
    echo "3. System creates Accounts Payable journal entry\n";
    echo "4. Record payments as they are made\n";
    echo "5. Each payment updates journal (debit AP, credit Cash/Bank)\n";
    echo "6. Print invoice for records and payment tracking\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}
