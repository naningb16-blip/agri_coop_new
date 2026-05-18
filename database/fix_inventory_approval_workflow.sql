-- ============================================================
-- Fix: Inventory Release Request Approval Workflow
-- ============================================================
-- Purpose: Ensure stock release requests properly route to GM for approval
-- Issue: Release requests from inventory dept not reflecting to GM
-- Date: 2026-05-06
-- ============================================================

USE agri_coop;

-- Step 1: Ensure approval chain exists for stock_release module
-- This defines the approval workflow: GM only (single step)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('stock_release', 1, 'gm', 'General Manager', 1);

-- Step 2: Add requesting_department column if it doesn't exist
-- This allows tracking which department is requesting the stock
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'stock_release_requests' 
    AND COLUMN_NAME = 'requesting_department'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE stock_release_requests ADD COLUMN requesting_department VARCHAR(50) NULL AFTER purpose',
    'SELECT "Column requesting_department already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Create approval requests for any orphaned pending release requests
-- This fixes any existing release requests that don't have approval requests
INSERT INTO approval_requests (module, reference_type, reference_id, title, description, current_step, requested_by, created_at)
SELECT 
    'stock_release' as module,
    'stock_release' as reference_type,
    srr.id as reference_id,
    CONCAT('Stock Release: ', p.name, ' x', FORMAT(srr.quantity, 2)) as title,
    CONCAT('Warehouse: ', w.name, ' | Purpose: ', COALESCE(srr.purpose, 'N/A'),
           IF(srr.requesting_department IS NOT NULL, CONCAT(' | Department: ', srr.requesting_department), '')) as description,
    1 as current_step,
    srr.requested_by,
    srr.created_at
FROM stock_release_requests srr
JOIN products p ON srr.product_id = p.id
JOIN warehouses w ON srr.warehouse_id = w.id
LEFT JOIN approval_requests ar ON ar.reference_type = 'stock_release' AND ar.reference_id = srr.id
WHERE srr.status = 'pending' AND ar.id IS NULL;

-- Step 4: Create approval steps for the newly created approval requests
-- For each approval request without steps, create the full approval chain
INSERT INTO approval_steps (request_id, step_order, approver_role, label, status)
SELECT 
    ar.id as request_id,
    ac.step_order,
    ac.approver_role,
    ac.label,
    'pending' as status
FROM approval_requests ar
CROSS JOIN approval_chains ac
LEFT JOIN approval_steps ast ON ast.request_id = ar.id AND ast.step_order = ac.step_order
WHERE ar.module = 'stock_release' 
  AND ac.module = 'stock_release'
  AND ar.status = 'pending'
  AND ast.id IS NULL
ORDER BY ar.id, ac.step_order;

-- Step 5: Create audit log entries for newly created approval requests
INSERT INTO approval_audit_log (request_id, step_order, actor_id, action, remarks, created_at)
SELECT 
    ar.id as request_id,
    1 as step_order,
    ar.requested_by as actor_id,
    'submitted' as action,
    'Request submitted (auto-created by fix script)' as remarks,
    ar.created_at
FROM approval_requests ar
LEFT JOIN approval_audit_log aal ON aal.request_id = ar.id AND aal.action = 'submitted'
WHERE ar.module = 'stock_release' 
  AND ar.status = 'pending'
  AND aal.id IS NULL;

-- Verification queries
SELECT '=== VERIFICATION RESULTS ===' as '';

SELECT 'Approval Chain for stock_release:' as '';
SELECT step_order, approver_role, label, is_gm_step 
FROM approval_chains 
WHERE module = 'stock_release' 
ORDER BY step_order;

SELECT 'Pending Release Requests with Approval Status:' as '';
SELECT 
    srr.id as release_id,
    srr.status as release_status,
    p.name as product,
    srr.quantity,
    u.full_name as requested_by,
    ar.id as approval_id,
    ar.status as approval_status,
    ar.current_step,
    acs.label as current_step_label
FROM stock_release_requests srr
JOIN products p ON srr.product_id = p.id
JOIN users u ON srr.requested_by = u.id
LEFT JOIN approval_requests ar ON ar.reference_type = 'stock_release' AND ar.reference_id = srr.id
LEFT JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
WHERE srr.status = 'pending'
ORDER BY srr.created_at DESC
LIMIT 10;

SELECT 'Pending Approvals for GM:' as '';
SELECT 
    ar.id,
    ar.module,
    ar.title,
    ar.current_step,
    acs.label as step_label,
    u.full_name as requested_by,
    ar.created_at
FROM approval_requests ar
JOIN approval_steps acs ON acs.request_id = ar.id AND acs.step_order = ar.current_step
JOIN users u ON ar.requested_by = u.id
WHERE ar.status = 'pending' 
  AND acs.status = 'pending' 
  AND acs.approver_role = 'gm'
ORDER BY ar.created_at ASC;

SELECT '=== FIX COMPLETE ===' as '';
