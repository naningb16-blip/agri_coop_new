-- Sales Department Invoice Feature
-- Adds invoice numbers for cash sales and printable invoices

-- Add invoice_number field to sales_orders
ALTER TABLE sales_orders
ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) UNIQUE AFTER so_number;

-- Add invoice_date field
ALTER TABLE sales_orders
ADD COLUMN IF NOT EXISTS invoice_date DATETIME AFTER invoice_number;

-- Update existing sales orders with invoice numbers (for cash sales only)
-- Cash sales are identified by payment_type = 'cash_receipt' in receipts table
UPDATE sales_orders so
LEFT JOIN receipts r ON r.reference_type = 'sale' AND r.reference_id = so.id
SET so.invoice_number = CONCAT('INV-', DATE_FORMAT(so.order_date, '%Y%m%d'), '-', LPAD(so.id, 4, '0')),
    so.invoice_date = so.created_at
WHERE so.invoice_number IS NULL 
  AND r.id IS NOT NULL;

SELECT '=== SALES INVOICE FEATURE INSTALLED ===' AS status;

-- Verify
SELECT 
    COUNT(*) AS total_orders,
    SUM(CASE WHEN invoice_number IS NOT NULL THEN 1 ELSE 0 END) AS with_invoice,
    SUM(CASE WHEN invoice_number IS NULL THEN 1 ELSE 0 END) AS without_invoice
FROM sales_orders;

SELECT '=== FEATURE READY ===' AS info;
