-- Operational Department Approval Workflow
-- Adds GM approval for production records and processing batches

-- Add approval chain for operational module (GM approval only)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('operational', 1, 'gm', 'General Manager Approval', 1);

-- Note: Processing batches already create approval requests in the code
-- This migration ensures the approval chain exists for GM to see and approve them

SELECT '=== OPERATIONAL APPROVAL WORKFLOW INSTALLED ===' AS status;

-- Verify approval chains
SELECT * FROM approval_chains WHERE module = 'operational';

SELECT '=== SETUP COMPLETE ===' AS info;
