-- Update all approval chains: manager approves first, then GM
-- Delete existing chains and rebuild cleanly
DELETE FROM approval_chains;

INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('sales',       1, 'manager', 'Manager',         0),
('sales',       2, 'gm',      'General Manager', 1),
('purchasing',  1, 'manager', 'Manager',         0),
('purchasing',  2, 'gm',      'General Manager', 1),
('inventory',   1, 'manager', 'Manager',         0),
('inventory',   2, 'gm',      'General Manager', 1),
('hr',          1, 'manager', 'Manager',         0),
('hr',          2, 'gm',      'General Manager', 1),
('finance',     1, 'manager', 'Manager',         0),
('finance',     2, 'gm',      'General Manager', 1),
('logistics',   1, 'manager', 'Manager',         0),
('logistics',   2, 'gm',      'General Manager', 1),
('production',  1, 'manager', 'Manager',         0),
('production',  2, 'gm',      'General Manager', 1),
('processing',  1, 'manager', 'Manager',         0),
('processing',  2, 'gm',      'General Manager', 1),
('qa',          1, 'manager', 'Manager',         0),
('qa',          2, 'gm',      'General Manager', 1),
('prs',         1, 'manager', 'Manager',         0),
('prs',         2, 'gm',      'General Manager', 1),
('withdrawal',  1, 'manager', 'Manager',         0),
('withdrawal',  2, 'gm',      'General Manager', 1),
('stock_release',1,'manager', 'Manager',         0),
('stock_release',2,'gm',      'General Manager', 1);
