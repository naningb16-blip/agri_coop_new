-- ============================================================
-- RBAC: Department Users, GM Gate, Board of Directors
-- ============================================================
USE agri_coop;

-- New roles
INSERT IGNORE INTO roles (name, description) VALUES
('gm',       'General Manager — approval gate between departments'),
('bod',      'Board of Directors — read-only view of GM-approved items'),
('sales_user',      'Sales department user'),
('purchasing_user', 'Purchasing department user'),
('inventory_user',  'Inventory / Warehouse department user'),
('hr_user',         'Human Resources department user'),
('finance_user',    'Finance department user'),
('logistics_user',  'Logistics department user'),
('production_user', 'Production department user'),
('processing_user', 'Processing department user'),
('qa_user',         'Quality Assurance department user');

-- Department-scoped permissions
INSERT IGNORE INTO permissions (module, action, description) VALUES
('sales',       'view',   'View sales module'),
('sales',       'create', 'Create sales orders'),
('purchasing',  'view',   'View purchasing module'),
('purchasing',  'create', 'Create POs and PRS'),
('inventory',   'view',   'View inventory module'),
('inventory',   'create', 'Create stock movements'),
('hr',          'view',   'View HR module'),
('hr',          'create', 'Manage employees and payroll'),
('finance',     'view',   'View finance module'),
('finance',     'create', 'Create receipts and expenses'),
('logistics',   'view',   'View logistics module'),
('logistics',   'create', 'Create deliveries'),
('production',  'view',   'View production module'),
('production',  'create', 'Create production records'),
('processing',  'view',   'View processing module'),
('processing',  'create', 'Create processing batches'),
('qa',          'view',   'View QA module'),
('qa',          'create', 'Create QA inspections'),
('bod',         'view',   'Board of Directors — view GM-approved items');

-- Wire dept roles to their module permissions
-- (run after inserting permissions above)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
ON (r.name='sales_user'      AND p.module='sales'      AND p.action IN ('view','create'))
OR (r.name='purchasing_user' AND p.module='purchasing' AND p.action IN ('view','create'))
OR (r.name='inventory_user'  AND p.module='inventory'  AND p.action IN ('view','create'))
OR (r.name='hr_user'         AND p.module='hr'         AND p.action IN ('view','create'))
OR (r.name='finance_user'    AND p.module='finance'    AND p.action IN ('view','create'))
OR (r.name='logistics_user'  AND p.module='logistics'  AND p.action IN ('view','create'))
OR (r.name='production_user' AND p.module='production' AND p.action IN ('view','create'))
OR (r.name='processing_user' AND p.module='processing' AND p.action IN ('view','create'))
OR (r.name='qa_user'         AND p.module='qa'         AND p.action IN ('view','create'))
OR (r.name='bod'             AND p.module='bod'        AND p.action='view');

-- Sample department users (password: password)
INSERT IGNORE INTO users (role_id, username, email, password, full_name) VALUES
((SELECT id FROM roles WHERE name='gm'),       'gm1',       'gm@agricoop.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'General Manager'),
((SELECT id FROM roles WHERE name='bod'),      'bod1',      'bod@agricoop.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Board Member'),
((SELECT id FROM roles WHERE name='sales_user'),      'sales_dept',      'sales@agricoop.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales Department'),
((SELECT id FROM roles WHERE name='purchasing_user'), 'purchasing_dept', 'purchasing@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Purchasing Department'),
((SELECT id FROM roles WHERE name='inventory_user'),  'inventory_dept',  'inventory@agricoop.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Inventory Department'),
((SELECT id FROM roles WHERE name='hr_user'),         'hr_dept',         'hr@agricoop.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Department'),
((SELECT id FROM roles WHERE name='finance_user'),    'finance_dept',    'finance@agricoop.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Department'),
((SELECT id FROM roles WHERE name='logistics_user'),  'logistics_dept',  'logistics@agricoop.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Logistics Department'),
((SELECT id FROM roles WHERE name='production_user'), 'production_dept', 'production@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Production Department'),
((SELECT id FROM roles WHERE name='processing_user'), 'processing_dept', 'processing@agricoop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Processing Department'),
((SELECT id FROM roles WHERE name='qa_user'),         'qa_dept',         'qa@agricoop.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'QA Department');

-- Update approval chains: step 1 = dept head (manager), step 2 = GM (gate)
-- Already seeded in approval_system.sql — just ensure gm role exists in chains
UPDATE approval_chains SET approver_role='gm' WHERE is_gm_step=1;
