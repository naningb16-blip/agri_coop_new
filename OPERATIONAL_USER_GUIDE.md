# Operational Department - User Account Guide

## Quick Start

### Test Account (Already Created)

To test the Operational department, use this account:

```
Username: operational
Password: operational123
Role: Operational Manager
Access: Full operational access
```

**To create this account, run:**
```bash
mysql -u root -p agri_coop < database/operational_test_user.sql
```

## Login and Access

1. Go to your application URL (e.g., `http://localhost/agri-coop`)
2. Login with the credentials above
3. Navigate to **Operational** in the menu
4. You'll see three tabs:
   - **Production** - Manage farming, planting, harvesting
   - **Processing** - Manage batch processing (drying, sorting, milling)
   - **Farmers** - Manage farmer information

## User Roles and What They Can Do

### 1. Operational Staff (Regular User)

**Permissions:**
- ✅ View all operational data
- ✅ Create production records
- ✅ Create processing batches
- ✅ Update statuses
- ✅ Manage stages
- ❌ Cannot delete records
- ❌ Cannot approve requests

**Typical Tasks:**
- Record new planting activities
- Track crop growth and harvesting
- Create processing batches
- Update processing stages
- Add production inputs (fertilizers, seeds)
- Manage farmer information

**SQL to create:**
```sql
-- Replace 'username' with actual username
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'username'),
    'operational',
    1, 1, 1, 0, 0
);
```

### 2. Operational Manager

**Permissions:**
- ✅ View all operational data
- ✅ Create production records
- ✅ Create processing batches
- ✅ Update statuses
- ✅ Manage stages
- ✅ Delete pending records
- ✅ Approve operational requests

**Typical Tasks:**
- Everything operational staff can do, PLUS:
- Approve processing batches
- Cancel batches if needed
- Delete incorrect records
- Oversee entire operational workflow

**SQL to create:**
```sql
-- Replace 'username' with actual username
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'username'),
    'operational',
    1, 1, 1, 1, 1
);
```

### 3. General Manager (GM)

**Permissions:**
- ✅ View all operational data (read-only)
- ❌ Cannot create or edit directly
- ✅ Can approve through Approvals section

**Typical Tasks:**
- Review operational performance
- View production and processing reports
- Approve operational requests through Approvals section
- Monitor operational metrics

**SQL to create:**
```sql
-- Replace 'username' with actual username
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'username'),
    'operational',
    1, 0, 0, 0, 1
);
```

## Creating New Operational Users

### Method 1: Through Admin Panel (Recommended)

1. Login as admin
2. Go to **Users** section
3. Click **Add User**
4. Fill in user details:
   - Username
   - Password
   - Full Name
   - Email
   - Role: Select "manager" for operational manager
5. Set permissions:
   - Module: **Operational**
   - Check appropriate permissions based on role

### Method 2: Using SQL

```sql
-- Step 1: Create the user account
INSERT INTO users (username, password, full_name, email, role, status, created_at)
VALUES (
    'john.doe',                                                          -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password hash
    'John Doe',                                                          -- full name
    'john.doe@agri-coop.local',                                         -- email
    'manager',                                                           -- role
    'active',                                                            -- status
    NOW()
);

-- Step 2: Grant operational permissions
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'john.doe'),
    'operational',
    1, -- can_view
    1, -- can_create
    1, -- can_update
    1, -- can_delete (0 for staff, 1 for manager)
    1  -- can_approve (0 for staff, 1 for manager)
);
```

**Note:** To generate a password hash in PHP:
```php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## Migrating Existing Users

If you have existing Production or Processing users, migrate them:

### Automatic Migration (Recommended)

```bash
mysql -u root -p agri_coop < database/operational_department_migration.sql
```

This automatically converts:
- All **Production** users → **Operational** users
- All **Processing** users → **Operational** users
- Maintains their existing permission levels

### Manual Migration

```sql
-- Update production users to operational
UPDATE user_permissions 
SET module = 'operational' 
WHERE module = 'production';

-- Update processing users to operational
UPDATE user_permissions 
SET module = 'operational' 
WHERE module = 'processing';

-- Remove duplicate permissions if any
DELETE up1 FROM user_permissions up1
INNER JOIN user_permissions up2 
WHERE up1.id > up2.id 
  AND up1.user_id = up2.user_id 
  AND up1.module = up2.module;
```

## Checking User Permissions

### View all operational users:

```sql
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.role,
    up.can_view AS 'View',
    up.can_create AS 'Create',
    up.can_update AS 'Update',
    up.can_delete AS 'Delete',
    up.can_approve AS 'Approve'
FROM users u
JOIN user_permissions up ON u.id = up.user_id
WHERE up.module = 'operational'
ORDER BY u.full_name;
```

### Check specific user:

```sql
SELECT 
    u.username,
    u.full_name,
    u.role,
    up.module,
    up.can_view,
    up.can_create,
    up.can_update,
    up.can_delete,
    up.can_approve
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
WHERE u.username = 'operational';  -- Replace with username
```

## Common Permission Combinations

### Full Access (Manager)
```sql
can_view = 1, can_create = 1, can_update = 1, can_delete = 1, can_approve = 1
```

### Standard Staff
```sql
can_view = 1, can_create = 1, can_update = 1, can_delete = 0, can_approve = 0
```

### Read-Only (GM)
```sql
can_view = 1, can_create = 0, can_update = 0, can_delete = 0, can_approve = 1
```

### View Only (Observer)
```sql
can_view = 1, can_create = 0, can_update = 0, can_delete = 0, can_approve = 0
```

## Testing User Access

After creating a user, test their access:

### Test Checklist

1. **Login Test**
   - [ ] Can login with username/password
   - [ ] Redirected to appropriate dashboard

2. **Navigation Test**
   - [ ] Can see "Operational" in menu
   - [ ] Can access `/operational` page

3. **Production Tab Test**
   - [ ] Can view production records
   - [ ] Can create new production record (if has permission)
   - [ ] Can update production status (if has permission)
   - [ ] Can view production details

4. **Processing Tab Test**
   - [ ] Can view processing batches
   - [ ] Can create new batch (if has permission)
   - [ ] Can manage stages (if has permission)
   - [ ] Can view batch details

5. **Farmers Tab Test**
   - [ ] Can view farmers list
   - [ ] Can add/edit farmers (if has permission)

6. **Permission Test**
   - [ ] Cannot perform actions without permission
   - [ ] Sees appropriate error messages
   - [ ] GM sees "view only" notice

## Troubleshooting

### Issue: User can't login

**Check:**
```sql
SELECT username, status FROM users WHERE username = 'username';
```

**Fix:**
```sql
UPDATE users SET status = 'active' WHERE username = 'username';
```

### Issue: User can't access operational page

**Check:**
```sql
SELECT * FROM user_permissions 
WHERE user_id = (SELECT id FROM users WHERE username = 'username')
  AND module = 'operational';
```

**Fix:**
```sql
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'username'),
    'operational',
    1, 1, 1, 0, 0
);
```

### Issue: User sees "Permission denied"

**Check what they're trying to do and update permissions:**
```sql
UPDATE user_permissions 
SET can_create = 1, can_update = 1  -- Adjust as needed
WHERE user_id = (SELECT id FROM users WHERE username = 'username')
  AND module = 'operational';
```

### Issue: Old production/processing permissions still exist

**Clean up:**
```sql
-- Remove old permissions
DELETE FROM user_permissions 
WHERE module IN ('production', 'processing');

-- Ensure operational permissions exist
-- (Run the migration script again)
```

## Password Reset

If you need to reset a user's password:

```sql
-- Reset to 'password123'
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'username';
```

Or generate a new hash in PHP:
```php
<?php
echo password_hash('new_password', PASSWORD_DEFAULT);
?>
```

## Summary

✅ **Test Account**: Use `operational` / `operational123` to test
✅ **Create Users**: Through admin panel or SQL
✅ **Migrate Users**: Run `operational_department_migration.sql`
✅ **Three Roles**: Staff (create/update), Manager (full access), GM (view/approve)
✅ **Check Access**: Use SQL queries to verify permissions

## Quick Commands Reference

```bash
# Create test account
mysql -u root -p agri_coop < database/operational_test_user.sql

# Migrate existing users
mysql -u root -p agri_coop < database/operational_department_migration.sql

# Check operational users
mysql -u root -p agri_coop -e "
SELECT u.username, u.full_name, up.can_view, up.can_create, up.can_update 
FROM users u 
JOIN user_permissions up ON u.id = up.user_id 
WHERE up.module = 'operational';
"
```

That's everything you need to manage operational department user accounts!
