# Operational Department - Migration Guide

## Overview

This document outlines the consolidation of **Production** and **Processing** departments into a single **Operational** department.

## What Changed

### Before
- **Production Department** - Managed farming, planting, harvesting
- **Processing Department** - Managed batch processing, drying, sorting, milling

### After
- **Operational Department** - Manages both production AND processing in one unified interface

## Benefits

1. **Unified Workflow** - Production and processing are part of the same operational flow
2. **Simplified Navigation** - One department instead of two
3. **Better Integration** - Easier to track from planting to finished product
4. **Reduced Complexity** - Single permission system, single menu item

## Implementation Status

### ✅ Completed

1. **New Controller Created**
   - File: `app/controllers/OperationalController.php`
   - Combines all Production and Processing functionality
   - Tab-based interface: Production | Processing | Farmers

2. **Routes Updated**
   - File: `public/index.php`
   - New routes under `/operational`
   - Legacy routes `/production` and `/processing` redirect to `/operational`

### 🔄 In Progress

3. **Views Need to be Created**
   - `app/views/operational/index.php` - Main page with tabs
   - `app/views/operational/production_detail.php` - Production record details
   - `app/views/operational/processing_detail.php` - Processing batch details
   - `app/views/operational/farmers.php` - Farmers management

4. **Permissions Need Update**
   - Change `production` permission to `operational`
   - Change `processing` permission to `operational`
   - Update user roles in database

5. **Menu/Navigation Update**
   - Update sidebar menu to show "Operational" instead of "Production" and "Processing"
   - Update dashboard links

6. **Database Updates**
   - Update `modules` table to rename modules
   - Update approval workflows module references

## Migration Steps

### Step 1: Update Permissions

```sql
-- Update module references
UPDATE modules SET name='operational', label='Operational' 
WHERE name IN ('production', 'processing');

-- Update user permissions
UPDATE user_permissions SET module='operational' 
WHERE module IN ('production', 'processing');

-- Update approval workflows
UPDATE approval_workflows SET module='operational' 
WHERE module IN ('production', 'processing');

-- Update approval requests
UPDATE approval_requests SET module='operational' 
WHERE module IN ('production', 'processing');
```

### Step 2: Create Views

The views need to be created by copying and modifying existing production and processing views:

**Main Index** (`app/views/operational/index.php`):
- Tabbed interface
- Tab 1: Production (from `app/views/production/index.php`)
- Tab 2: Processing (from `app/views/processing/index.php`)
- Tab 3: Farmers (from `app/views/production/farmers.php`)

**Detail Views**:
- Copy `app/views/production/detail.php` → `app/views/operational/production_detail.php`
- Copy `app/views/processing/detail.php` → `app/views/operational/processing_detail.php`
- Update all form actions and links to use `/operational/` prefix

### Step 3: Update Navigation Menu

Update the main layout file to show "Operational" menu item:

```php
// Before:
<a href="/production">Production</a>
<a href="/processing">Processing</a>

// After:
<a href="/operational">Operational</a>
```

### Step 4: Update Dashboard

Update dashboard controller and views to link to `/operational` instead of `/production` or `/processing`.

### Step 5: Test

- [ ] Can access `/operational` page
- [ ] Production tab works
- [ ] Processing tab works
- [ ] Farmers tab works
- [ ] Can create production records
- [ ] Can create processing batches
- [ ] Can update statuses
- [ ] Inventory integration still works
- [ ] Approvals still work
- [ ] Legacy URLs redirect properly

## Controller Methods Mapping

### Production Methods

| Old Route | New Route | Method |
|-----------|-----------|--------|
| GET /production | GET /operational?tab=production | index() |
| POST /production/create | POST /operational/create-production | createProduction() |
| GET /production/detail | GET /operational/production-detail | productionDetail() |
| POST /production/status | POST /operational/production-status | updateProductionStatus() |
| POST /production/add-input | POST /operational/add-input | addInput() |
| POST /production/add-schedule | POST /operational/add-schedule | addSchedule() |
| POST /production/update-schedule | POST /operational/update-schedule | updateSchedule() |
| GET /production/farmers | GET /operational?tab=farmers | index() |
| POST /production/save-farmer | POST /operational/save-farmer | saveFarmer() |

### Processing Methods

| Old Route | New Route | Method |
|-----------|-----------|--------|
| GET /processing | GET /operational?tab=processing | index() |
| POST /processing/create | POST /operational/create-processing | createProcessing() |
| GET /processing/detail | GET /operational/processing-detail | processingDetail() |
| POST /processing/update-stage | POST /operational/update-stage | updateStage() |
| POST /processing/cancel | POST /operational/cancel-processing | cancelProcessing() |
| POST /processing/delete | POST /operational/delete-processing | deleteProcessing() |

## Permission Changes

### Before
```php
$this->requirePermission('production', 'view');
$this->requirePermission('processing', 'view');
```

### After
```php
$this->requirePermission('operational', 'view');
```

## Database Schema

No changes to database tables needed! The following tables remain unchanged:
- `production_records`
- `production_inputs`
- `production_schedules`
- `processing_batches`
- `processing_stage_logs`
- `farmers`

Only module/permission references need updating.

## Backward Compatibility

Legacy URLs are supported via redirects:
- `/production` → `/operational?tab=production`
- `/processing` → `/operational?tab=processing`

This ensures:
- Old bookmarks still work
- External links don't break
- Gradual migration possible

## User Impact

### For Operational Staff
- **Single login location** - Access both production and processing from one place
- **Unified dashboard** - See all operational activities together
- **Easier workflow** - Track items from planting through processing

### For Management
- **Better oversight** - View entire operational flow in one place
- **Simplified approvals** - All operational approvals in one module
- **Clearer reporting** - Combined operational metrics

### For IT/Admin
- **Simpler permissions** - One module instead of two
- **Easier maintenance** - Single codebase for operations
- **Better organization** - Logical grouping of related functions

## Rollback Plan

If issues arise, rollback is simple:

1. Restore old routes in `public/index.php`
2. Revert permission changes in database
3. Keep using `ProductionController` and `ProcessingController`
4. Delete `OperationalController.php`

The old controllers are still in place and functional.

## Next Steps

1. **Create view files** - Copy and adapt existing views
2. **Update permissions** - Run SQL migration
3. **Update navigation** - Modify layout/menu files
4. **Test thoroughly** - Verify all functionality works
5. **Train users** - Show staff the new unified interface
6. **Monitor** - Watch for issues in first week
7. **Remove old controllers** - After successful migration (optional)

## Support

For questions or issues during migration:
- Check this document first
- Test in development environment
- Keep old controllers as backup
- Document any custom changes

## Summary

The Operational department consolidation:
- ✅ Simplifies the system
- ✅ Improves user experience
- ✅ Maintains all functionality
- ✅ Supports backward compatibility
- ✅ Easy to rollback if needed

**Status**: Controller created, routes updated. Views and permissions need completion.
