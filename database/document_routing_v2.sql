-- ============================================================
-- Document Routing v2 — Hard Stop Rejection with Full Visibility
-- ============================================================
USE agri_coop;

-- Add rejection tracking columns to documents table
ALTER TABLE routed_documents
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT,
    ADD COLUMN IF NOT EXISTS rejected_by      INT NULL,
    ADD COLUMN IF NOT EXISTS rejected_at      TIMESTAMP NULL,
    ADD FOREIGN KEY (rejected_by) REFERENCES users(id);
-- Ensure status enum includes all needed values
ALTER TABLE routed_documents
    MODIFY COLUMN status ENUM('routing','approved','rejected') DEFAULT 'routing';

-- Ensure step status has all values
ALTER TABLE document_routing_steps
    MODIFY COLUMN status ENUM('pending','approved','rejected','skipped') DEFAULT 'pending';

-- Ensure log action has all values  
ALTER TABLE document_routing_log
    MODIFY COLUMN action ENUM('uploaded','approved','rejected','skipped','commented') NOT NULL;
