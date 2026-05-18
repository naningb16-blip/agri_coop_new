-- ============================================================
-- Inventory & Warehouse Module Migration
-- ============================================================
USE agri_coop;

-- Stock release requests (approval-gated stock-out)
CREATE TABLE IF NOT EXISTS stock_release_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    product_id   INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity     DECIMAL(12,2) NOT NULL,
    purpose      VARCHAR(255),
    requested_by INT NOT NULL,
    status       ENUM('pending','approved','rejected','released') DEFAULT 'pending',
    released_at  TIMESTAMP NULL,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)   REFERENCES products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (requested_by) REFERENCES users(id)
);

-- Returns (from sales or purchases)
CREATE TABLE IF NOT EXISTS stock_returns (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reference_type ENUM('sale','purchase','internal') NOT NULL,
    reference_id   INT NOT NULL DEFAULT 0,
    product_id     INT NOT NULL,
    warehouse_id   INT NOT NULL,
    quantity       DECIMAL(12,2) NOT NULL,
    reason         TEXT,
    condition_type ENUM('good','damaged','expired') DEFAULT 'good',
    status         ENUM('pending','approved','restocked','disposed') DEFAULT 'pending',
    created_by     INT NOT NULL,
    reviewed_by    INT NULL,
    reviewed_at    TIMESTAMP NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)   REFERENCES products(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (created_by)   REFERENCES users(id),
    FOREIGN KEY (reviewed_by)  REFERENCES users(id)
);

-- Approval chain for stock releases (GM only)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('stock_release', 1, 'gm', 'General Manager', 1);
