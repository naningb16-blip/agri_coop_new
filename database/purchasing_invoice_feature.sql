-- Purchasing Department Invoice Feature
-- Adds supplier invoice numbers, journal entries for payables, and printable invoices

-- Add supplier_invoice_number field to purchase_orders
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS supplier_invoice_number VARCHAR(100) AFTER po_number;

-- Add supplier_invoice_date field
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS supplier_invoice_date DATE AFTER supplier_invoice_number;

-- Add payment_terms field (e.g., Net 30, Net 60, COD)
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS payment_terms VARCHAR(50) DEFAULT 'Net 30' AFTER supplier_invoice_date;

-- Add payment_due_date field
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS payment_due_date DATE AFTER payment_terms;

-- Add payment_status field
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' AFTER payment_due_date;

-- Add amount_paid field
ALTER TABLE purchase_orders
ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(12,2) DEFAULT 0 AFTER payment_status;

SELECT '=== PURCHASING INVOICE FEATURE INSTALLED ===' AS status;

-- Verify
SELECT 
    COUNT(*) AS total_pos,
    SUM(CASE WHEN supplier_invoice_number IS NOT NULL THEN 1 ELSE 0 END) AS with_invoice,
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid,
    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid
FROM purchase_orders;

SELECT '=== FEATURE READY ===' AS info;
