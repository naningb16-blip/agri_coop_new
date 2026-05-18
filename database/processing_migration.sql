-- ============================================================
-- Processing Module Migration
-- ============================================================
USE agri_coop;

-- Extend process_type to include shelling
ALTER TABLE processing_batches
    MODIFY COLUMN process_type ENUM('drying','sorting','shelling','bagging','milling') NOT NULL;

-- Add warehouse tracking and created_by
ALTER TABLE processing_batches
    ADD COLUMN IF NOT EXISTS input_warehouse_id  INT NULL,
    ADD COLUMN IF NOT EXISTS output_warehouse_id INT NULL,
    ADD COLUMN IF NOT EXISTS created_by          INT NULL,
    ADD FOREIGN KEY (input_warehouse_id)  REFERENCES warehouses(id),
    ADD FOREIGN KEY (output_warehouse_id) REFERENCES warehouses(id),
    ADD FOREIGN KEY (created_by)          REFERENCES users(id);

-- Stage-level log: each batch can have multiple stage entries
CREATE TABLE IF NOT EXISTS processing_stage_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    batch_id     INT NOT NULL,
    stage        ENUM('drying','sorting','shelling','bagging','milling') NOT NULL,
    stage_order  INT NOT NULL DEFAULT 1,
    input_qty    DECIMAL(12,2) NOT NULL,
    output_qty   DECIMAL(12,2) DEFAULT 0,
    waste_qty    DECIMAL(12,2) DEFAULT 0,
    started_at   DATETIME NULL,
    completed_at DATETIME NULL,
    status       ENUM('pending','in_progress','completed','skipped') DEFAULT 'pending',
    notes        TEXT,
    recorded_by  INT NULL,
    FOREIGN KEY (batch_id)    REFERENCES processing_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Approval chain for processing batches
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('processing', 1, 'manager', 'Processing Supervisor', 0),
('processing', 2, 'gm',      'General Manager',       1);
