-- ============================================================
-- Purchasing Module Migration
-- ============================================================
USE agri_coop;

-- Items for purchase requisitions (purchase_requisitions already exists)
CREATE TABLE IF NOT EXISTS purchase_requisition_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit VARCHAR(50),
    estimated_price DECIMAL(12,2) DEFAULT 0,
    total_price DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (requisition_id) REFERENCES purchase_requisitions(id) ON DELETE CASCADE
);

-- Add notes + approved_by to purchase_requisitions if missing
ALTER TABLE purchase_requisitions
    ADD COLUMN IF NOT EXISTS notes TEXT,
    ADD COLUMN IF NOT EXISTS approved_by INT NULL,
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL,
    ADD FOREIGN KEY (approved_by) REFERENCES users(id);

-- Add approval chain for PRS
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('prs', 1, 'manager', 'Department Head', 0),
('prs', 2, 'gm',      'General Manager', 1);
