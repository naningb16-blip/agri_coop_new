-- Operational Department Test User Account
-- Creates a test user for the operational department

USE agri_coop;

-- Ensure operational role exists
INSERT IGNORE INTO roles (name, description) VALUES
('operational_user', 'Operational department user (Production + Processing)');

-- Ensure operational permissions exist
INSERT IGNORE INTO permissions (module, action, description) VALUES
('operational', 'view',   'View operational module'),
('operational', 'create', 'Create production records and processing batches');

-- Wire operational role to operational permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='operational_user' AND p.module='operational' AND p.action IN ('view','create');

-- Create test operational user (if not exists)
-- Username: operational
-- Password: operational123 (hashed with password_hash)

INSERT INTO users (role_id, username, password, full_name, email, status, created_at)
VALUES (
    (SELECT id FROM roles WHERE name = 'operational_user'),
    'operational',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: operational123
    'Operational Manager',
    'operational@agri-coop.local',
    'active',
    NOW()
)
ON DUPLICATE KEY UPDATE 
    full_name = 'Operational Manager',
    role_id = (SELECT id FROM roles WHERE name = 'operational_user'),
    status = 'active';

-- Verify the account was created
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.email,
    r.name AS role,
    u.status
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username = 'operational';

-- Display login credentials
SELECT 
    '=== OPERATIONAL DEPARTMENT TEST ACCOUNT ===' AS info
UNION ALL
SELECT 'Username: operational'
UNION ALL
SELECT 'Password: operational123'
UNION ALL
SELECT 'Role: Operational User'
UNION ALL
SELECT 'Permissions: View and Create (Production + Processing)'
UNION ALL
SELECT 'Access: /operational (Production, Processing, Farmers tabs)'
UNION ALL
SELECT '========================================';
