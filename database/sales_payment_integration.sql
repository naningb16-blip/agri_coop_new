-- Sales Payment Integration Migration
-- Adds payment tracking to sales orders and links to finance receipts

USE agri_coop;

-- Add payment fields to sales_orders
ALTER TABLE sales_orders
    ADD COLUMN IF NOT EXISTS payment_type ENUM('cash','charge','credit') DEFAULT 'cash' AFTER total_amount,
    ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid' AFTER payment_type,
    ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(12,2) DEFAULT 0 AFTER payment_status,
    ADD COLUMN IF NOT EXISTS receipt_id INT NULL AFTER amount_paid,
    ADD KEY IF NOT EXISTS idx_payment_status (payment_status),
    ADD KEY IF NOT EXISTS idx_receipt_id (receipt_id);

-- Update existing sales orders to have payment_type='cash' and payment_status='unpaid'
UPDATE sales_orders 
SET payment_type = 'cash', 
    payment_status = 'unpaid',
    amount_paid = 0
WHERE payment_type IS NULL;
