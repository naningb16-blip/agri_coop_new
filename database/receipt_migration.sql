-- ============================================================
-- Receipt Enhancement: Cash Receipt / Charge Invoice
-- ============================================================
USE agri_coop;

ALTER TABLE receipts
    ADD COLUMN IF NOT EXISTS receipt_type      ENUM('cash_receipt','charge_invoice') DEFAULT 'cash_receipt',
    ADD COLUMN IF NOT EXISTS item_description  VARCHAR(255),
    ADD COLUMN IF NOT EXISTS quantity          DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unit              VARCHAR(50);
