# Quick Fix: Inventory Release Requests Not Showing for GM

## Problem
Release requests from the Inventory department are not appearing in the GM's pending approvals.

## Quick Solution

### Option 1: Web Browser (Easiest)
1. Open your browser and go to: `http://your-domain/fix_inventory_approvals.php`
2. The script will automatically fix everything
3. Refresh your GM dashboard

### Option 2: Command Line
1. Run: `fix_inventory_approvals.bat`
2. Enter your MySQL credentials when prompted
3. The script will fix the database

### Option 3: Manual SQL
1. Open your MySQL client
2. Run the file: `database/fix_inventory_approval_workflow.sql`

## What Gets Fixed

✅ Adds approval chain for stock_release module (GM only - single step)  
✅ Adds missing `requesting_department` column  
✅ Creates approval requests for orphaned release requests  
✅ Links all pending releases to the approval system  

## Verify It Worked

1. Go to: `http://your-domain/check_inventory_approval.php`
2. Check that:
   - Approval chain exists for stock_release
   - Pending releases have approval requests
   - GM can see them in pending approvals

## Test the Fix

1. **As Inventory User:**
   - Go to Inventory → Release Requests
   - Create a new release request
   - Submit it

2. **As GM:**
   - Go to Dashboard or Approvals
   - You should see the new request in "My Pending Approvals"
   - Click Review → Approve/Reject

3. **Verify:**
   - If approved, stock should be deducted
   - Requester should get a notification

## Files Created

- `database/fix_inventory_approval_workflow.sql` - SQL fix script
- `public/fix_inventory_approvals.php` - Web-based fix tool
- `public/check_inventory_approval.php` - Diagnostic tool
- `fix_inventory_approvals.bat` - Windows batch script
- `INVENTORY_APPROVAL_FIX.md` - Detailed documentation

## Still Not Working?

Check these:

1. **GM user role:** Ensure GM user has `role='gm'` in database
2. **Approval chain:** Run diagnostic to verify chain exists
3. **Current step:** Check that approval requests are at step 1 (GM step)
4. **Error logs:** Check PHP error logs for exceptions

## Support

If you need help, check:
- `INVENTORY_APPROVAL_FIX.md` for detailed technical documentation
- PHP error logs in your server
- `approval_audit_log` table for approval history

---
**Date:** May 6, 2026  
**Status:** Ready to deploy
