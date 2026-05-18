-- Quick script to create operational test user
-- Run this: mysql -u root -p agri_coop < create_operational_user.sql

USE agri_coop;

-- First, ensure the operational role exists
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

-- Delete existing operational user if exists (to recreate with correct password)
DELETE FROM users WHERE username = 'operational';

-- Create operational test user
-- Username: operational
-- Password: operational123
INSERT INTO users (role_id, username, password, full_name, email, status, created_at)
VALUES (
    (SELECT id FROM roles WHERE name = 'operational_user'),
    'operational',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Operational Manager',
    'operational@agri-coop.local',
    'active',
    NOW()
);

-- Verify user was created
SELECT 
    '=== USER CREATED SUCCESSFULLY ===' AS status,
    u.id,
    u.username,
    'operational123' AS password,
    u.full_name,
    r.name AS role,
    u.status
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username = 'operational';
