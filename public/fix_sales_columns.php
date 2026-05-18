<?php
/**
 * Emergency Fix: Add missing payment columns to sales_orders table
 * Run this script once to fix the database schema
 */

require_once __DIR__ . '/../config/database.php';

// Prevent any output before headers
ob_start();

echo "=== Sales Payment Columns Fix ===\n\n";

try {
    // Create connection using config constants
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to database: " . DB_NAME . "\n\n";
    
    // Add columns directly without reading SQL file
    echo "Adding payment columns to sales_orders table...\n\n";
    
    // Check and add payment_type
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'payment_type'");
    if ($result->num_rows == 0) {
        echo "Adding payment_type column... ";
        $conn->query("ALTER TABLE sales_orders ADD COLUMN payment_type ENUM('cash','charge','credit') DEFAULT 'cash' AFTER total_amount");
        echo "✓\n";
    } else {
        echo "payment_type column already exists ✓\n";
    }
    
    // Check and add payment_status
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'payment_status'");
    if ($result->num_rows == 0) {
        echo "Adding payment_status column... ";
        $conn->query("ALTER TABLE sales_orders ADD COLUMN payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid' AFTER payment_type");
        echo "✓\n";
    } else {
        echo "payment_status column already exists ✓\n";
    }
    
    // Check and add amount_paid
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'amount_paid'");
    if ($result->num_rows == 0) {
        echo "Adding amount_paid column... ";
        $conn->query("ALTER TABLE sales_orders ADD COLUMN amount_paid DECIMAL(12,2) DEFAULT 0 AFTER payment_status");
        echo "✓\n";
    } else {
        echo "amount_paid column already exists ✓\n";
    }
    
    // Check and add receipt_id
    $result = $conn->query("SHOW COLUMNS FROM sales_orders LIKE 'receipt_id'");
    if ($result->num_rows == 0) {
        echo "Adding receipt_id column... ";
        $conn->query("ALTER TABLE sales_orders ADD COLUMN receipt_id INT NULL AFTER amount_paid");
        echo "✓\n";
    } else {
        echo "receipt_id column already exists ✓\n";
    }
    
    // Update existing records
    echo "\nUpdating existing records... ";
    $conn->query("UPDATE sales_orders SET payment_type = COALESCE(payment_type, 'cash'), payment_status = COALESCE(payment_status, 'unpaid'), amount_paid = COALESCE(amount_paid, 0) WHERE payment_status IS NULL OR payment_type IS NULL");
    echo "✓\n";
    
    // Add indexes
    echo "\nAdding indexes...\n";
    $result = $conn->query("SHOW INDEX FROM sales_orders WHERE Key_name = 'idx_payment_status'");
    if ($result->num_rows == 0) {
        echo "Adding idx_payment_status... ";
        $conn->query("ALTER TABLE sales_orders ADD KEY idx_payment_status (payment_status)");
        echo "✓\n";
    } else {
        echo "idx_payment_status already exists ✓\n";
    }
    
    $result = $conn->query("SHOW INDEX FROM sales_orders WHERE Key_name = 'idx_receipt_id'");
    if ($result->num_rows == 0) {
        echo "Adding idx_receipt_id... ";
        $conn->query("ALTER TABLE sales_orders ADD KEY idx_receipt_id (receipt_id)");
        echo "✓\n";
    } else {
        echo "idx_receipt_id already exists ✓\n";
    }
    
    // Verify
    echo "\n=== Verification ===\n";
    $result = $conn->query("SHOW COLUMNS FROM sales_orders WHERE Field IN ('payment_type', 'payment_status', 'amount_paid', 'receipt_id')");
    echo "Columns added: " . $result->num_rows . "/4\n\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "✓ " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    $conn->close();
    
    echo "\n=== Migration Complete ===\n";
    echo "✓ Sales payment columns added successfully!\n";
    echo "✓ You can now access the Sales page.\n\n";
    echo "⚠️  IMPORTANT: Delete this file for security:\n";
    echo "   rm public/fix_sales_columns.php\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Flush output
ob_end_flush();
