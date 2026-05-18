-- ============================================================
-- Document Routing / File Upload System
-- ============================================================
USE agri_coop;

-- Uploaded documents
CREATE TABLE IF NOT EXISTS routed_documents (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    file_name    VARCHAR(255) NOT NULL,
    file_path    VARCHAR(500) NOT NULL,
    file_size    INT DEFAULT 0,
    file_type    VARCHAR(100),
    uploaded_by  INT NOT NULL,
    origin_dept  VARCHAR(100) NOT NULL,   -- role name of uploader e.g. 'sales_user'
    status       ENUM('routing','approved','rejected') DEFAULT 'routing',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- One row per department step in the routing chain
CREATE TABLE IF NOT EXISTS document_routing_steps (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    document_id  INT NOT NULL,
    dept_role    VARCHAR(100) NOT NULL,   -- e.g. 'sales_user', 'purchasing_user'
    dept_label   VARCHAR(100) NOT NULL,   -- e.g. 'Sales', 'Purchasing'
    step_order   INT NOT NULL,
    status       ENUM('pending','approved','rejected','skipped','returned') DEFAULT 'pending',
    actioned_by  INT NULL,
    remarks      TEXT,
    actioned_at  TIMESTAMP NULL,
    FOREIGN KEY (document_id) REFERENCES routed_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (actioned_by) REFERENCES users(id)
);

-- Audit trail for document routing
CREATE TABLE IF NOT EXISTS document_routing_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    actor_id    INT NOT NULL,
    action      ENUM('uploaded','approved','rejected','skipped','commented') NOT NULL,
    dept_role   VARCHAR(100),
    remarks     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES routed_documents(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id)    REFERENCES users(id)
);
