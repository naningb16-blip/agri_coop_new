# Operational Department - Complete Deployment Guide

## Overview

This guide will help you fully deploy the Operational department, which combines Production and Processing into a single unified interface.

## Prerequisites

- Database access (MySQL/MariaDB)
- Admin access to the application
- Backup of current database (recommended)

## Deployment Steps

### Step 1: Backup Database

```bash
mysqldump -u root -p agri_coop > backup_before_operational_$(date +%Y%m%d).sql
```

### Step 2: Run the Migration

This will:
- Create the `operational_user` role
- Add operational permissions
- Migrate existing production/processing users to operational
- Update module references

```bash
mysql -u root -p agri_coop < database/operational_department_migration.sql
```

**Expected Output:**
```
=== OPERATIONAL ROLE ===
id | name             | description
12 | operational_user | Operational department user (Production + Processing)

=== OPERATIONAL PERMISSIONS ===
module      | action | description
operational | view   | View operational module
operational | create | Create production records and processing batches

=== OPERATIONAL USERS ===
(Shows migrated users)

=== OLD USERS (should be 0) ===
old_users_count
0

=== MIGRATION COMPLETE ===
```

### Step 3: Create Test User (Optional)

```bash
mysql -u root -p agri_coop < database/operational_test_user.sql
```

**Test Credentials:**
- Username: `operational`
- Password: `operational123`

### Step 4: Verify in Admin Panel

1. Login as admin
2. Go to **Users** section
3. Click **Add User**
4. In the **Role** dropdown, you should now see:
   - ✅ `operational_user` (NEW!)
   - `production_user` (old, will be migrated)
   - `processing_user` (old, will be migrated)
   - Other roles...

### Step 5: Test the Operational Department

1. **Login** with test account or create a new operational user
2. **Navigate** to `/operational` or click "Operational" in menu
3. **Verify** you see three tabs:
   - Production
   - Processing
   - Farmers
4. **Test** creating a production record
5. **Test** creating a processing batch
6. **Test** managing farmers

## What Changed

### Database Changes

#### New Role
```sql
roles table:
- operational_user (combines production + processing)
```

#### New Permissions
```sql
permissions table:
- operational | view
- operational | create
```

#### Migrated Users
- All `production_user` → `operational_user`
- All `processing_user` → `operational_user`

### Application Changes

#### New Routes
- `GET /operational` - Main page with tabs
- `GET /operational?tab=production` - Production tab
- `GET /operational?tab=processing` - Processing tab
- `GET /operational?tab=farmers` - Farmers tab
- `POST /operational/create-production` - Create production record
- `POST /operational/create-processing` - Create processing batch
- And more...

#### Legacy Routes (Still Work)
- `/production` → redirects to `/operational?tab=production`
- `/processing` → redirects to `/operational?tab=processing`

## Creating New Operational Users

### Method 1: Through Admin Panel (Recommended)

1. Login as admin
2. Go to **Users** section
3. Click **Add User**
4. Fill in details:
   - Full Name: `John Doe`
   - Username: `john.doe`
   - Email: `john.doe@agri-coop.local`
   - **Role**: Select `operational_user` ✅
   - Password: Set password
   - Status: Active
5. Click **Save**

### Method 2: Using SQL

```sql
-- Create user
INSERT INTO users (role_id, username, password, full_name, email, status)
VALUES (
    (SELECT id FROM roles WHERE name = 'operational_user'),
    'john.doe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    'John Doe',
    'john.doe@agri-coop.local',
    'active'
);
```

## Verification Checklist

- [ ] Migration ran successfully
- [ ] `operational_user` role exists in database
- [ ] Operational permissions exist
- [ ] Old production/processing users migrated
- [ ] `operational_user` appears in admin user dropdown
- [ ] Can create new operational users
- [ ] Can access `/operational` page
- [ ] Production tab works
- [ ] Processing tab works
- [ ] Farmers tab works
- [ ] Can create production records
- [ ] Can create processing batches
- [ ] Legacy URLs redirect properly

## Troubleshooting

### Issue: "operational_user" not in dropdown

**Check if role exists:**
```sql
SELECT * FROM roles WHERE name = 'operational_user';
```

**If not found, run migration again:**
```bash
mysql -u root -p agri_coop < database/operational_department_migration.sql
```

### Issue: User can't access /operational

**Check user's role:**
```sql
SELECT u.username, r.name AS role
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username = 'username';
```

**Update user to operational:**
```sql
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name = 'operational_user')
WHERE username = 'username';
```

### Issue: Permission denied

**Check role permissions:**
```sql
SELECT r.name AS role, p.module, p.action
FROM roles r
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE r.name = 'operational_user';
```

**Should show:**
```
role             | module      | action
operational_user | operational | view
operational_user | operational | create
```

### Issue: Old production/processing users still exist

**Check for old users:**
```sql
SELECT u.username, r.name AS role
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE r.name IN ('production_user', 'processing_user');
```

**Migrate them:**
```sql
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name = 'operational_user')
WHERE role_id IN (
    SELECT id FROM roles WHERE name IN ('production_user', 'processing_user')
);
```

## Rollback Plan

If you need to rollback:

### 1. Restore Database Backup
```bash
mysql -u root -p agri_coop < backup_before_operational_YYYYMMDD.sql
```

### 2. Or Manual Rollback
```sql
-- Restore production users
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name = 'production_user')
WHERE role_id = (SELECT id FROM roles WHERE name = 'operational_user')
  AND username LIKE '%production%';

-- Restore processing users
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name = 'processing_user')
WHERE role_id = (SELECT id FROM roles WHERE name = 'operational_user')
  AND username LIKE '%processing%';

-- Remove operational role
DELETE FROM role_permissions WHERE role_id = (SELECT id FROM roles WHERE name = 'operational_user');
DELETE FROM roles WHERE name = 'operational_user';
DELETE FROM permissions WHERE module = 'operational';
```

## Post-Deployment

### Update Navigation Menu

Update your main layout file to show "Operational" instead of separate "Production" and "Processing":

**Before:**
```php
<a href="/production">Production</a>
<a href="/processing">Processing</a>
```

**After:**
```php
<a href="/operational">Operational</a>
```

### Update Dashboard Links

Update any dashboard widgets or links that point to `/production` or `/processing` to point to `/operational` instead.

### Train Users

- Show users the new tabbed interface
- Explain that Production and Processing are now in one place
- Demonstrate how to switch between tabs
- Update any user documentation

## Benefits

✅ **Unified Interface** - Production and processing in one place  
✅ **Easier Navigation** - One menu item instead of two  
✅ **Better Workflow** - Track from planting through processing  
✅ **Simplified Permissions** - One role instead of two  
✅ **Backward Compatible** - Old URLs still work  

## Support

If you encounter issues:

1. Check this guide's troubleshooting section
2. Verify migration ran successfully
3. Check database for operational role and permissions
4. Test with the test account (`operational` / `operational123`)
5. Review application logs for errors

## Summary

The operational department is now fully deployed! Users can:
- Create operational users through admin panel
- Access unified Production + Processing interface
- Manage production records, processing batches, and farmers
- Use legacy URLs (they redirect automatically)

**Status**: ✅ READY FOR PRODUCTION USE
