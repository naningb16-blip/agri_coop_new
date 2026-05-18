-- ============================================================
-- QA, Production, Cost Monitoring Migration
-- ============================================================
USE agri_coop;

-- ── Quality Assurance ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS qa_inspections (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reference_type  ENUM('return','batch','seed','delivery','purchase_order') NOT NULL,
    reference_id    INT NOT NULL,
    product_id      INT NOT NULL,
    warehouse_id    INT NULL,
    inspected_by    INT NOT NULL,
    inspection_date DATE NOT NULL,
    result          ENUM('passed','failed','conditional') DEFAULT 'passed',
    moisture_pct    DECIMAL(5,2) DEFAULT 0,
    foreign_matter  DECIMAL(5,2) DEFAULT 0,
    germination_pct DECIMAL(5,2) DEFAULT 0,
    sample_qty      DECIMAL(12,2) DEFAULT 0,
    approved_qty    DECIMAL(12,2) DEFAULT 0,
    rejected_qty    DECIMAL(12,2) DEFAULT 0,
    remarks         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id)   REFERENCES products(id),
    FOREIGN KEY (inspected_by) REFERENCES users(id)
);

-- ── Production / Farm ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS farmers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(150) NOT NULL,
    phone        VARCHAR(50),
    address      TEXT,
    farm_area_ha DECIMAL(10,2) DEFAULT 0,
    status       ENUM('active','inactive') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS production_records (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id        INT NOT NULL,
    product_id       INT NOT NULL,
    farm_location    VARCHAR(255),
    season           VARCHAR(50),
    planting_date    DATE,
    expected_harvest DATE,
    actual_harvest   DATE,
    planted_area_ha  DECIMAL(10,2),
    expected_yield   DECIMAL(12,2),
    actual_yield     DECIMAL(12,2),
    status           ENUM('planned','planted','growing','harvested','completed') DEFAULT 'planned',
    notes            TEXT,
    created_by       INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id)  REFERENCES farmers(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS production_inputs (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    production_record_id INT NOT NULL,
    input_type           ENUM('fertilizer','pesticide','seed','labor','other') NOT NULL,
    name                 VARCHAR(150) NOT NULL,
    quantity             DECIMAL(12,2),
    unit                 VARCHAR(50),
    unit_cost            DECIMAL(12,2) DEFAULT 0,
    total_cost           DECIMAL(12,2) DEFAULT 0,
    applied_date         DATE,
    notes                TEXT,
    FOREIGN KEY (production_record_id) REFERENCES production_records(id) ON DELETE CASCADE
);

-- ── Cost Monitoring ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS batch_costs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    batch_id     INT NOT NULL,
    cost_type    ENUM('labor','material','overhead','utility','other') NOT NULL,
    description  VARCHAR(255),
    amount       DECIMAL(12,2) NOT NULL,
    recorded_by  INT,
    recorded_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id)    REFERENCES processing_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS production_schedules (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    production_record_id INT NOT NULL,
    activity             VARCHAR(150) NOT NULL,
    scheduled_date       DATE NOT NULL,
    completed_date       DATE NULL,
    status               ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    assigned_to          INT NULL,
    notes                TEXT,
    FOREIGN KEY (production_record_id) REFERENCES production_records(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to)          REFERENCES employees(id)
);

-- Approval chain for QA
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('qa', 1, 'manager', 'QA Supervisor', 0),
('qa', 2, 'gm',      'General Manager', 1);
