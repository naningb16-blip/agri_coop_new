-- Add 'pending' status to employees table for approval workflow
ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','terminated','pending') DEFAULT 'active';

-- Add HR approval chain (GM approves new employee additions)
INSERT IGNORE INTO approval_chains (module, step_order, approver_role, label, is_gm_step)
VALUES ('hr', 1, 'gm', 'GM Approval', 1);
