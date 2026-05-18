-- ============================================================
-- Fix: Stock Release Approval Chain - GM Only
-- ============================================================
-- Purpose: Change stock release approval from 2-step to 1-step (GM only)
-- Previous: Manager -> GM
-- New: GM only
-- Date: 2026-05-06
-- ============================================================

USE agri_coop;

-- Step 1: Remove old approval chain for stock_release
DELETE FROM approval_chains WHERE module = 'stock_release';

-- Step 2: Add new single-step approval chain (GM only)
INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('stock_release', 1, 'gm', 'General Manager', 1);

-- Step 3: Update existing pending approval requests to use the new chain
-- Delete old approval steps for pending stock_release requests
DELETE ast FROM approval_steps ast
JOIN approval_requests ar ON ast.request_id = ar.id
WHERE ar.module = 'stock_release' 
  AND ar.status = 'pending';

-- Step 4: Recreate approval steps with the new single-step chain
INSERT INTO approval_steps (request_id, step_order, approver_role, label, status)
SELECT 
    ar.id as request_id,
    1 as step_order,
    'gm' as approver_role,
    'General Manager' as label,
    'pending' as status
FROM approval_requests ar
WHERE ar.module = 'stock_release' 
  AND ar.status = 'pending';

-- Step 5: Reset current_step to 1 for all pending stock_release requests
UPDATE approval_requests 
SET current_step = 1 
WHERE module = 'stock_release' 
  AND status = 'pending';

-- Verification
SELECT '=== VERIFICATION ===' as '';

SELECT 'New Approval Chain (should show only GM):' as '';
SELECT step_order, approver_role, label, is_gm_step 
FROM approval_chains 
WHERE module = 'stock_release' 
ORDER BY step_order;

SELECT 'Pending Stock Release Requests:' as '';
SELECT 
    ar.id,
    ar.title,
    ar.current_step,
    ar.status,
    COUNT(ast.id) as total_steps,
    SUM(CASE WHEN ast.approver_role = 'gm' THEN 1 ELSE 0 END) as gm_steps
FROM approval_requests ar
LEFT JOIN approval_steps ast ON ast.request_id = ar.id
WHERE ar.module = 'stock_release' 
  AND ar.status = 'pending'
GROUP BY ar.id, ar.title, ar.current_step, ar.status;

SELECT '=== FIX COMPLETE ===' as '';
SELECT 'Stock release requests now go directly to GM for approval.' as message;
