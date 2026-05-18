-- Operational Department Migration
-- Combines Production and Processing into single Operational department

USE agri_coop;

-- ============================================================
-- Step 1: Add Operational Role and Permissions
-- ============================================================

-- Add operational_user role
INSERT IGNORE INTO roles (name, description) VALUES
('operational_user', 'Operational department user (Production + Processing)');

-- Add operational permissions
INSERT IGNORE INTO permissions (module, action, description) VALUES
('operational', 'view',   'View operational module'),
('operational', 'create', 'Create production records and processing batches');

-- Wire operational role to operational permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='operational_user' AND p.module='operational' AND p.action IN ('view','create');

-- ============================================================
-- Step 2: Migrate Existing Users
-- ============================================================

-- Update production users to operational users
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name='operational_user')
WHERE role_id = (SELECT id FROM roles WHERE name='production_user');

-- Update processing users to operational users
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name='operational_user')
WHERE role_id = (SELECT id FROM roles WHERE name='processing_user');

-- ============================================================
-- Step 3: Update Module References
-- ============================================================

-- Update approval workflows
UPDATE approval_workflows 
SET module = 'operational' 
WHERE module IN ('production', 'processing');

-- Update approval requests (if table exists)
UPDATE approval_requests 
SET module = 'operational' 
WHERE module IN ('production', 'processing');

-- ============================================================
-- Verification Queries
-- ============================================================

-- Check operational role exists
SELECT '=== OPERATIONAL ROLE ===' AS info;
SELECT * FROM roles WHERE name = 'operational_user';

-- Check operational permissions
SELECT '=== OPERATIONAL PERMISSIONS ===' AS info;
SELECT * FROM permissions WHERE module = 'operational';

-- Check operational users
SELECT '=== OPERATIONAL USERS ===' AS info;
SELECT u.id, u.username, u.full_name, r.name AS role
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE r.name = 'operational_user';

-- Check no old production/processing users remain
SELECT '=== OLD USERS (should be 0) ===' AS info;
SELECT COUNT(*) AS old_users_count
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE r.name IN ('production_user', 'processing_user');

SELECT '=== MIGRATION COMPLETE ===' AS info;
