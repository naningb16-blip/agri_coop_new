# Stock Release Approval - GM Only Update

## Change Summary

**Previous Workflow:** Manager → GM (2 steps)  
**New Workflow:** GM only (1 step)

Stock release requests from the Inventory department now go directly to the GM for approval, bypassing the warehouse manager step.

## Quick Update

### Option 1: Web Browser (Recommended)
```
http://your-domain/update_stock_release_to_gm_only.php
```
This will automatically:
- Remove the old 2-step approval chain
- Add the new GM-only approval chain
- Update all pending approval requests to use the new workflow

### Option 2: SQL Script
```bash
mysql -u username -p agri_coop < database/fix_stock_release_approval_chain.sql
```

## What Changes

### Database Changes

**Before:**
```sql
approval_chains:
  Step 1: manager (Warehouse Manager)
  Step 2: gm (General Manager)
```

**After:**
```sql
approval_chains:
  Step 1: gm (General Manager)
```

### Workflow Impact

**Before:**
1. Inventory user submits release request
2. Request goes to Warehouse Manager
3. Manager approves → forwards to GM
4. GM approves → stock released

**After:**
1. Inventory user submits release request
2. Request goes directly to GM
3. GM approves → stock released

## Benefits

✅ Faster approval process (1 step instead of 2)  
✅ Direct GM oversight of all stock releases  
✅ Simplified workflow for inventory users  
✅ Reduced approval bottlenecks  

## Existing Pending Requests

All existing pending stock release requests will be automatically updated to:
- Remove the manager approval step
- Set current step to 1 (GM)
- Appear in GM's pending approvals immediately

## Testing

After applying the update:

1. **As Inventory User:**
   - Create a new stock release request
   - Verify it goes directly to GM (no manager step)

2. **As GM:**
   - Check pending approvals
   - Should see all stock release requests
   - Approve/reject directly

3. **Verify:**
   - Stock is deducted when approved
   - No intermediate manager approval required

## Files

- `database/fix_stock_release_approval_chain.sql` - SQL migration
- `public/update_stock_release_to_gm_only.php` - Web-based updater
- `database/inventory_migration.sql` - Updated base migration
- `database/fix_inventory_approval_workflow.sql` - Updated fix script

## Rollback

If you need to revert to the 2-step workflow:

```sql
DELETE FROM approval_chains WHERE module = 'stock_release';

INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('stock_release', 1, 'manager', 'Warehouse Manager', 0),
('stock_release', 2, 'gm', 'General Manager', 1);
```

Then update pending requests accordingly.

## Support

If you encounter issues:
1. Check that GM user has `role='gm'` in the database
2. Verify approval_chains table has the correct entry
3. Check approval_audit_log for any errors
4. Review PHP error logs

---
**Date:** May 6, 2026  
**Status:** Ready to deploy
