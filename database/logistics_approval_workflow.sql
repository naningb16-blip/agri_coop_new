-- Logistics Department Approval Workflow
-- Adds GM approval for all deliveries (inbound and outbound)

-- Add approval chain for logistics module (GM approval only)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('logistics', 1, 'gm', 'General Manager Approval', 1);

-- Note: Deliveries now create approval requests in the code
-- This migration ensures the approval chain exists for GM to see and approve them

SELECT '=== LOGISTICS APPROVAL WORKFLOW INSTALLED ===' AS status;

-- Verify approval chains
SELECT * FROM approval_chains WHERE module = 'logistics';

SELECT '=== SETUP COMPLETE ===' AS info;
