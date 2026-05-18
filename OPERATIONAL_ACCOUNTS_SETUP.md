# Operational Department - User Accounts Setup Guide

## Overview

When migrating from Production + Processing to Operational department, existing user accounts need to be updated to use the new "operational" permission.

## Current User Accounts

### Before Migration

Users currently have one of these department assignments:
- **Production Department** - Can access `/production` routes
- **Processing Department** - Can access `/processing` routes

### After Migration

Users will have:
- **Operational Department** - Can access `/operational` routes (includes both production AND processing)

## Automatic Migration

The SQL migration script automatically updates all user permissions:

```sql
-- This is already in: database/operational_department_migration.sql

-- Update user permissions
UPDATE user_permissions 
SET module = 'operational' 
WHERE module IN ('production', 'processing');
```

## Running the Migration

### Step 1: Backup Database

```bash
mysqldump -u [username] -p agri_coop > backup_before_operational_migration.sql
```

### Step 2: Run Migration

```bash
mysql -u [username] -p agri_coop < database/operational_department_migration.sql
```

### Step 3: Verify

```sql
-- Check operational module exists
SELECT * FROM modules WHERE name = 'operational';

-- Check user permissions updated
SELECT u.username, up.module, up.can_view, up.can_create, up.can_update, up.can_delete, up.can_approve
FROM users u
JOIN user_permissions up ON u.id = up.user_id
WHERE up.module = 'operational';

-- Should show NO production or processing permissions
SELECT COUNT(*) FROM user_permissions WHERE module IN ('production', 'processing');
-- Expected result: 0
```

## Manual User Account Setup

If you need to manually create or update user accounts for the Operational department:

### Option 1: Through Admin Panel

1. Login as admin
2. Go to **Users** section
3. Edit user
4. Change department from "Production" or "Processing" to "Operational"
5. Set permissions:
   - ✅ View - Can see operational data
   - ✅ Create - Can create production records and processing batches
   - ✅ Update - Can update statuses and stages
   - ✅ Delete - Can delete pending records
   - ✅ Approve - Can approve operational requests (usually manager only)

### Option 2: Direct SQL

```sql
-- Update existing production user to operational
UPDATE user_permissions 
SET module = 'operational' 
WHERE user_id = [USER_ID] AND module = 'production';

-- Update existing processing user to operational
UPDATE user_permissions 
SET module = 'operational' 
WHERE user_id = [USER_ID] AND module = 'processing';

-- Create new operational user permission
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES ([USER_ID], 'operational', 1, 1, 1, 0, 0);
```

## User Roles and Permissions

### Operational Staff (Regular User)

**Permissions:**
- ✅ View - See all production and processing records
- ✅ Create - Create new production records and processing batches
- ✅ Update - Update statuses, add inputs, manage schedules
- ❌ Delete - Cannot delete records
- ❌ Approve - Cannot approve requests

**What they can do:**
- Create production records (planting, harvesting)
- Create processing batches (drying, sorting, milling)
- Update production statuses
- Manage processing stages
- Add production inputs
- Create schedules
- Manage farmers

### Operational Manager

**Permissions:**
- ✅ View
- ✅ Create
- ✅ Update
- ✅ Delete - Can delete pending records
- ✅ Approve - Can approve operational requests

**What they can do:**
- Everything operational staff can do, PLUS:
- Delete pending production records
- Delete pending processing batches
- Approve operational requests
- Cancel processing batches

### General Manager (GM)

**Permissions:**
- ✅ View - Read-only access
- ❌ Create - Cannot create (view only)
- ❌ Update - Cannot update (view only)
- ❌ Delete - Cannot delete
- ✅ Approve - Can approve through Approvals section

**What they can do:**
- View all operational data
- Approve operational requests through Approvals section
- Cannot directly manage production or processing

## Example User Accounts

### Example 1: Operational Staff Member

```sql
-- User: Juan Dela Cruz
-- Role: Operational Staff
-- Can: Create and manage production/processing
-- Cannot: Approve or delete

INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'juan.delacruz'),
    'operational',
    1, -- can_view
    1, -- can_create
    1, -- can_update
    0, -- can_delete
    0  -- can_approve
);
```

### Example 2: Operational Manager

```sql
-- User: Maria Santos
-- Role: Operational Manager
-- Can: Full operational access including approvals

INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'maria.santos'),
    'operational',
    1, -- can_view
    1, -- can_create
    1, -- can_update
    1, -- can_delete
    1  -- can_approve
);
```

### Example 3: General Manager

```sql
-- User: Pedro Reyes (GM)
-- Role: General Manager
-- Can: View only, approve through Approvals section

-- GM already has 'gm' role, just ensure operational view permission
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES (
    (SELECT id FROM users WHERE username = 'pedro.reyes'),
    'operational',
    1, -- can_view
    0, -- can_create (view only)
    0, -- can_update (view only)
    0, -- can_delete
    1  -- can_approve (through Approvals section)
);
```

## Checking Current User Permissions

### Query to see all operational users:

```sql
SELECT 
    u.id,
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
JOIN user_permissions up ON u.id = up.user_id
WHERE up.module = 'operational'
ORDER BY u.full_name;
```

### Query to find users who still have old permissions:

```sql
SELECT 
    u.id,
    u.username,
    u.full_name,
    up.module
FROM users u
JOIN user_permissions up ON u.id = up.user_id
WHERE up.module IN ('production', 'processing')
ORDER BY u.full_name;
```

If this returns any rows, run the migration again.

## Department Field in Users Table

If your `users` table has a `department` field, you may also want to update it:

```sql
-- Update department field for users
UPDATE users 
SET department = 'operational' 
WHERE department IN ('production', 'processing');
```

## Testing User Access

After migration, test with each user type:

### Test 1: Operational Staff
1. Login as operational staff user
2. Navigate to `/operational`
3. Should see: Production, Processing, and Farmers tabs
4. Try creating a production record - Should work ✅
5. Try creating a processing batch - Should work ✅
6. Try deleting a record - Should fail ❌ (no permission)

### Test 2: Operational Manager
1. Login as operational manager
2. Navigate to `/operational`
3. Should see all tabs
4. Try all operations - Should work ✅
5. Try approving a request - Should work ✅

### Test 3: General Manager
1. Login as GM
2. Navigate to `/operational`
3. Should see all data (read-only)
4. Try creating/editing - Should see "view only" message
5. Go to Approvals section - Should be able to approve ✅

## Troubleshooting

### Issue: User can't access /operational

**Check:**
```sql
SELECT * FROM user_permissions 
WHERE user_id = [USER_ID] AND module = 'operational';
```

**Fix:**
```sql
INSERT INTO user_permissions (user_id, module, can_view, can_create, can_update, can_delete, can_approve)
VALUES ([USER_ID], 'operational', 1, 1, 1, 0, 0);
```

### Issue: User sees "Permission denied"

**Check permissions:**
```sql
SELECT can_view, can_create, can_update, can_delete, can_approve
FROM user_permissions 
WHERE user_id = [USER_ID] AND module = 'operational';
```

**Fix:** Update the specific permission that's needed.

### Issue: Old production/processing permissions still exist

**Fix:**
```sql
DELETE FROM user_permissions 
WHERE module IN ('production', 'processing');
```

Then ensure operational permissions are set for those users.

## Summary

✅ **Automatic Migration**: Run `database/operational_department_migration.sql`
✅ **Existing Users**: Automatically updated from production/processing to operational
✅ **New Users**: Create with "operational" module permission
✅ **Permissions**: Same permission levels (view, create, update, delete, approve)
✅ **Access**: Users can now access both production AND processing in one place

## Quick Commands

```bash
# Backup
mysqldump -u root -p agri_coop > backup.sql

# Migrate
mysql -u root -p agri_coop < database/operational_department_migration.sql

# Verify
mysql -u root -p agri_coop -e "SELECT * FROM modules WHERE name='operational';"
mysql -u root -p agri_coop -e "SELECT COUNT(*) as operational_users FROM user_permissions WHERE module='operational';"
```

That's it! Your user accounts are now ready for the Operational department.
