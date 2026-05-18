-- ============================================================
-- Logistics Module Migration
-- ============================================================
USE agri_coop;

-- Delivery receipts (DR) — generated when a delivery is confirmed
CREATE TABLE IF NOT EXISTS delivery_receipts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id     INT NOT NULL,
    dr_number       VARCHAR(100) NOT NULL UNIQUE,
    received_by     INT NOT NULL,
    received_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    condition_notes TEXT,
    signature_name  VARCHAR(150),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id)
);

-- Delivery items — what was actually received/dispatched
CREATE TABLE IF NOT EXISTS delivery_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    product_id  INT NOT NULL,
    quantity    DECIMAL(12,2) NOT NULL,
    unit        VARCHAR(50),
    notes       TEXT,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id)  REFERENCES products(id)
);

-- Add approval chain for logistics dispatches
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('logistics', 1, 'manager', 'Logistics Manager', 0),
('logistics', 2, 'gm',      'General Manager',   1);
