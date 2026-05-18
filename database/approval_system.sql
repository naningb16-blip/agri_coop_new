-- ============================================================
-- Multi-Level Approval System Migration
-- ============================================================

USE agri_coop;

-- Defines the ordered approval chain per module
CREATE TABLE IF NOT EXISTS approval_chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    step_order INT NOT NULL,
    approver_role VARCHAR(100) NOT NULL,   -- role name that can approve this step
    label VARCHAR(150) NOT NULL,           -- e.g. "Department Head", "General Manager"
    is_gm_step TINYINT(1) DEFAULT 0,       -- GM gate: next dept step only unlocks after this
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chain (module, step_order)
);

-- One request per document needing approval
CREATE TABLE IF NOT EXISTS approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    reference_type VARCHAR(100) NOT NULL,
    reference_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    current_step INT NOT NULL DEFAULT 1,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    requested_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id)
);

-- One row per step per request; tracks each level's decision
CREATE TABLE IF NOT EXISTS approval_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    step_order INT NOT NULL,
    approver_role VARCHAR(100) NOT NULL,
    label VARCHAR(150) NOT NULL,
    status ENUM('pending','approved','rejected','skipped') DEFAULT 'pending',
    actioned_by INT NULL,
    remarks TEXT,
    actioned_at TIMESTAMP NULL,
    FOREIGN KEY (request_id) REFERENCES approval_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (actioned_by) REFERENCES users(id)
);

-- Immutable audit trail
CREATE TABLE IF NOT EXISTS approval_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    step_order INT NOT NULL,
    actor_id INT NOT NULL,
    action ENUM('submitted','approved','rejected','commented') NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES approval_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id)
);

-- ============================================================
-- Default approval chains
-- Step 1: Department Head  (any manager role)
-- Step 2: General Manager  (gm role — the gate)
-- Step 3: Finance / next dept (only reached after GM approves)
-- ============================================================
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
-- Purchasing
('purchasing', 1, 'manager',  'Department Head',   0),
('purchasing', 2, 'gm',       'General Manager',   1),
('purchasing', 3, 'finance',  'Finance Officer',   0),
-- Sales
('sales',      1, 'manager',  'Department Head',   0),
('sales',      2, 'gm',       'General Manager',   1),
-- HR / Payroll
('hr',         1, 'manager',  'HR Manager',        0),
('hr',         2, 'gm',       'General Manager',   1),
('hr',         3, 'finance',  'Finance Officer',   0),
-- Expenses
('finance',    1, 'manager',  'Department Head',   0),
('finance',    2, 'gm',       'General Manager',   1);

-- Add GM role if not present
INSERT IGNORE INTO roles (name, description) VALUES
('gm',      'General Manager — approval gate'),
('finance', 'Finance Officer');
