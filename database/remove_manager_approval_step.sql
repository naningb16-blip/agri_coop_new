-- ============================================================
-- Remove Manager/Department Head from Approval Chain
-- GM becomes the only approver for all department requests
-- ============================================================

USE agri_coop;

-- Clear existing approval chains
DELETE FROM approval_chains;

-- Create simplified approval chains - GM only
INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
-- Purchasing - GM only
('purchasing', 1, 'gm', 'General Manager', 1),

-- Sales - GM only  
('sales', 1, 'gm', 'General Manager', 1),

-- HR/Payroll - GM only
('hr', 1, 'gm', 'General Manager', 1),

-- Finance/Expenses - GM only
('finance', 1, 'gm', 'General Manager', 1),

-- Inventory - GM only
('inventory', 1, 'gm', 'General Manager', 1),

-- Logistics - GM only
('logistics', 1, 'gm', 'General Manager', 1),

-- Production - GM only
('production', 1, 'gm', 'General Manager', 1),

-- Processing - GM only
('processing', 1, 'gm', 'General Manager', 1),

-- QA - GM only
('qa', 1, 'gm', 'General Manager', 1),

-- Documents - GM only
('documents', 1, 'gm', 'General Manager', 1);

-- Update any existing pending approval steps to point to GM
UPDATE approval_steps 
SET approver_role = 'gm', 
    label = 'General Manager',
    step_order = 1
WHERE status = 'pending';

-- Update approval requests to set current_step to 1 (GM step)
UPDATE approval_requests 
SET current_step = 1 
WHERE status = 'pending';

-- Remove any approval steps beyond step 1 for pending requests
DELETE FROM approval_steps 
WHERE step_order > 1 
AND request_id IN (
    SELECT id FROM approval_requests WHERE status = 'pending'
);

-- Optional: Remove manager role if no longer needed
-- UPDATE users SET role = 'admin' WHERE role = 'manager';
-- DELETE FROM roles WHERE name = 'manager';

SELECT 'Approval chains updated - GM is now the only approver for all departments' as message;