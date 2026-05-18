ALTER TABLE receipts
    ADD COLUMN IF NOT EXISTS receipt_number   VARCHAR(50),
    ADD COLUMN IF NOT EXISTS payer_name       VARCHAR(150),
    ADD COLUMN IF NOT EXISTS created_by       INT NULL,
    ADD COLUMN IF NOT EXISTS receipt_type     ENUM('cash_receipt','charge_invoice') DEFAULT 'cash_receipt',
    ADD COLUMN IF NOT EXISTS item_description VARCHAR(255),
    ADD COLUMN IF NOT EXISTS quantity         DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unit             VARCHAR(50),
    ADD COLUMN IF NOT EXISTS payment_method   VARCHAR(50) DEFAULT 'cash';
