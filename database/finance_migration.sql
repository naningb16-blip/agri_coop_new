-- ============================================================
-- Finance Module Migration
-- ============================================================
USE agri_coop;

-- Journal entries — double-entry ledger
CREATE TABLE IF NOT EXISTS journal_entries (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    entry_date  DATE NOT NULL,
    reference   VARCHAR(100),
    description VARCHAR(255) NOT NULL,
    debit_account  VARCHAR(100) NOT NULL,
    credit_account VARCHAR(100) NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    source_type VARCHAR(100),   -- sale, purchase, payroll, expense, receipt
    source_id   INT,
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Extend receipts: add receipt_number, payer info
ALTER TABLE receipts
    ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(100) UNIQUE,
    ADD COLUMN IF NOT EXISTS payer_name     VARCHAR(150),
    ADD COLUMN IF NOT EXISTS created_by     INT NULL,
    ADD FOREIGN KEY (created_by) REFERENCES users(id);

-- Extend expenses: add payment_method, receipt_number
ALTER TABLE expenses
    ADD COLUMN IF NOT EXISTS payment_method ENUM('cash','bank_transfer','check') DEFAULT 'cash',
    ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(100);

-- Approval chain for expenses
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('finance', 1, 'manager', 'Finance Manager', 0),
('finance', 2, 'gm',      'General Manager', 1);
