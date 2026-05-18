ALTER TABLE production_records
    ADD COLUMN IF NOT EXISTS land_owner        VARCHAR(150)   NULL,
    ADD COLUMN IF NOT EXISTS variety           VARCHAR(100)   NULL,
    ADD COLUMN IF NOT EXISTS seed_grower_name  VARCHAR(150)   NULL,
    ADD COLUMN IF NOT EXISTS no_of_seed_kgs    DECIMAL(10,2)  DEFAULT 0,
    ADD COLUMN IF NOT EXISTS fertilizer_used   TEXT           NULL,
    ADD COLUMN IF NOT EXISTS actual_harvest    DATE           NULL,
    ADD COLUMN IF NOT EXISTS milling_kgs       DECIMAL(10,2)  DEFAULT 0,
    ADD COLUMN IF NOT EXISTS bagging_bags      DECIMAL(10,2)  DEFAULT 0;

ALTER TABLE receipts
    ADD COLUMN IF NOT EXISTS receipt_number   VARCHAR(100),
    ADD COLUMN IF NOT EXISTS payer_name       VARCHAR(150),
    ADD COLUMN IF NOT EXISTS created_by       INT NULL,
    ADD COLUMN IF NOT EXISTS receipt_type     ENUM('cash_receipt','charge_invoice') DEFAULT 'cash_receipt',
    ADD COLUMN IF NOT EXISTS item_description VARCHAR(255),
    ADD COLUMN IF NOT EXISTS quantity         DECIMAL(12,2)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS unit             VARCHAR(50);

ALTER TABLE expenses
    ADD COLUMN IF NOT EXISTS payment_method   ENUM('cash','bank_transfer','check') DEFAULT 'cash',
    ADD COLUMN IF NOT EXISTS receipt_number   VARCHAR(100);

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS employee_type    ENUM('labor','management') DEFAULT 'labor';

ALTER TABLE routed_documents
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT           NULL,
    ADD COLUMN IF NOT EXISTS rejected_by      INT            NULL,
    ADD COLUMN IF NOT EXISTS rejected_at      DATETIME       NULL;
