-- ============================================================
-- Farmer Ledger System
-- ============================================================
USE agri_coop;

-- Add balance tracking to farmers table
ALTER TABLE farmers
    ADD COLUMN IF NOT EXISTS balance DECIMAL(12,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS total_credits DECIMAL(12,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS total_debits  DECIMAL(12,2) DEFAULT 0.00;

-- Ledger entries (double-entry style)
CREATE TABLE IF NOT EXISTS farmer_ledger (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id       INT NOT NULL,
    type            ENUM('credit','debit') NOT NULL,
    category        ENUM('sale','withdrawal','adjustment','advance','other') NOT NULL,
    reference_type  VARCHAR(100),          -- 'sales_order', 'withdrawal', 'manual'
    reference_id    INT,
    amount          DECIMAL(12,2) NOT NULL,
    running_balance DECIMAL(12,2) NOT NULL, -- balance after this transaction
    description     VARCHAR(255),
    transaction_date DATE NOT NULL,
    recorded_by     INT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id)   REFERENCES farmers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Withdrawal requests (approval-gated)
CREATE TABLE IF NOT EXISTS farmer_withdrawals (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id    INT NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    reason       TEXT,
    status       ENUM('pending','approved','rejected','released') DEFAULT 'pending',
    requested_by INT NOT NULL,
    approved_by  INT NULL,
    approved_at  TIMESTAMP NULL,
    released_at  TIMESTAMP NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id)    REFERENCES farmers(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by)  REFERENCES users(id)
);

-- Approval chain for withdrawals (GM-only)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('withdrawal', 1, 'gm', 'General Manager', 1);
