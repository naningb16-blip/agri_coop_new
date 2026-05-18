<?php
/**
 * Create Operational Test User
 * Access: http://localhost/agri-coop/public/create_operational_user.php
 */

require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();

echo "<h2>Creating Operational Test User</h2>";

// Step 1: Ensure operational role exists
echo "<h3>Step 1: Checking operational role...</h3>";
$role = $db->fetchOne("SELECT * FROM roles WHERE name = 'operational_user'");
if (!$role) {
    $db->query(
        "INSERT INTO roles (name, description) VALUES (?, ?)",
        ['operational_user', 'Operational department user (Production + Processing)'],
        'ss'
    );
    echo "✅ Created operational_user role<br>";
} else {
    echo "✅ operational_user role already exists (ID: {$role['id']})<br>";
}

// Step 2: Ensure permissions exist
echo "<h3>Step 2: Checking operational permissions...</h3>";
$perms = [
    ['operational', 'view', 'View operational module'],
    ['operational', 'create', 'Create production records and processing batches']
];
foreach ($perms as [$module, $action, $desc]) {
    $exists = $db->fetchOne(
        "SELECT * FROM permissions WHERE module = ? AND action = ?",
        [$module, $action],
        'ss'
    );
    if (!$exists) {
        $db->query(
            "INSERT INTO permissions (module, action, description) VALUES (?, ?, ?)",
            [$module, $action, $desc],
            'sss'
        );
        echo "✅ Created permission: {$module}.{$action}<br>";
    } else {
        echo "✅ Permission exists: {$module}.{$action}<br>";
    }
}

// Step 3: Wire role to permissions
echo "<h3>Step 3: Wiring role to permissions...</h3>";
$db->query("
    INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id FROM roles r JOIN permissions p
    WHERE r.name='operational_user' AND p.module='operational' AND p.action IN ('view','create')
");
echo "✅ Role permissions configured<br>";

// Step 4: Create user
echo "<h3>Step 4: Creating user...</h3>";

// Delete if exists
$existing = $db->fetchOne("SELECT * FROM users WHERE username = 'operational'");
if ($existing) {
    $db->query("DELETE FROM users WHERE username = 'operational'");
    echo "⚠️ Deleted existing operational user<br>";
}

// Get role ID
$role = $db->fetchOne("SELECT id FROM roles WHERE name = 'operational_user'");
if (!$role) {
    die("❌ ERROR: operational_user role not found!");
}

// Create password hash
$password = 'operational123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$userId = $db->insert(
    "INSERT INTO users (role_id, username, password, full_name, email, status, created_at)
     VALUES (?, ?, ?, ?, ?, 'active', NOW())",
    [
        $role['id'],
        'operational',
        $passwordHash,
        'Operational Manager',
        'operational@agri-coop.local'
    ],
    'issss'
);

if ($userId) {
    echo "✅ User created successfully (ID: {$userId})<br>";
    
    // Verify
    echo "<h3>Step 5: Verification</h3>";
    $user = $db->fetchOne("
        SELECT u.*, r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = 'operational'
    ");
    
    if ($user) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h4>✅ User Created Successfully!</h4>";
        echo "<strong>Username:</strong> operational<br>";
        echo "<strong>Password:</strong> operational123<br>";
        echo "<strong>Full Name:</strong> {$user['full_name']}<br>";
        echo "<strong>Email:</strong> {$user['email']}<br>";
        echo "<strong>Role:</strong> {$user['role_name']}<br>";
        echo "<strong>Status:</strong> {$user['status']}<br>";
        echo "<br><strong>You can now login with these credentials!</strong>";
        echo "</div>";
        
        // Test password
        echo "<h3>Step 6: Password Verification</h3>";
        if (password_verify($password, $user['password'])) {
            echo "✅ Password hash is correct and will work for login<br>";
        } else {
            echo "❌ WARNING: Password hash verification failed!<br>";
        }
    }
} else {
    echo "❌ Failed to create user<br>";
}

echo "<hr>";
echo "<p><a href='/agri-coop/public/'>← Back to Login</a></p>";
?>
