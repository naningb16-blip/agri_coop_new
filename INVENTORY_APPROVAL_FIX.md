# Inventory Release Request Approval Fix

## Issue Description
Release requests from the Inventory department are not appearing in the GM's pending approvals dashboard.

## Root Cause Analysis

### 1. Missing Approval Chain
The `approval_chains` table may be missing entries for the `stock_release` module. Without this chain, the approval workflow cannot route requests to the GM.

**Expected Chain:**
- Step 1: General Manager (role: `gm`) ← **Direct GM approval (single step)**

### 2. Missing Database Column
The `stock_release_requests` table is missing the `requesting_department` column that the controller code expects.

### 3. Orphaned Release Requests
Existing pending release requests may not have corresponding `approval_requests` entries, causing them to be invisible to the approval system.

### 4. Dashboard Query Issue
The GM dashboard query in `DashboardController::gmDashboard()` fetches ALL pending approval requests but doesn't properly filter by the current approval step.

## Solution

### Step 1: Run the Fix SQL Script

Execute the following SQL file to fix the database:

```bash
# Navigate to your project directory
cd /path/to/your/project

# Run the fix script
mysql -u your_username -p agri_coop < database/fix_inventory_approval_workflow.sql
```

Or access it via your web browser:
```
http://your-domain/fix_inventory_approvals.php
```

This script will:
1. ✅ Add the approval chain for `stock_release` module (GM only - single step)
2. ✅ Add the `requesting_department` column if missing
3. ✅ Create approval requests for orphaned release requests
4. ✅ Create approval steps for all pending requests
5. ✅ Add audit log entries for tracking

### Step 2: Verify the Fix

Access the diagnostic page:
```
http://your-domain/check_inventory_approval.php
```

This will show you:
- ✅ Approval chain configuration
- ✅ Table structure
- ✅ Recent release requests and their approval status
- ✅ Pending approvals for GM

### Step 3: Test the Workflow

1. **Create a New Release Request:**
   - Login as an Inventory user
   - Go to Inventory → Release Requests tab
   - Click "Request Release"
   - Fill in the form and submit

2. **Verify GM Can See It:**
   - Login as GM
   - Go to Dashboard or Approvals page
   - The new release request should appear in "My Pending Approvals"

3. **Approve the Request:**
   - Click "Review" on the pending approval
   - Click "Approve" or "Reject"
   - Verify the stock is deducted (if approved)

## Technical Details

### Approval Workflow

```
User submits release request
    ↓
InventoryController::requestRelease()
    ↓
Creates stock_release_requests record
    ↓
ApprovalModel::createRequest()
    ↓
Creates approval_requests record
    ↓
Creates approval_steps (Step 1: GM only)
    ↓
GM sees it in pending approvals
    ↓
GM approves/rejects
    ↓
ApprovalModel::actOnStep()
    ↓
If approved: ApprovalModel::_syncApproval()
    ↓
Creates stock movement (deducts inventory)
    ↓
Updates stock_release_requests.status = 'released'
```

### Key Files Modified

1. **database/fix_inventory_approval_workflow.sql** - Main fix script
2. **public/check_inventory_approval.php** - Diagnostic tool
3. **public/fix_inventory_approvals.php** - Web-based fix tool

### Database Changes

**approval_chains table:**
```sql
INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('stock_release', 1, 'gm', 'General Manager', 1);
```

**stock_release_requests table:**
```sql
ALTER TABLE stock_release_requests 
ADD COLUMN requesting_department VARCHAR(50) NULL AFTER purpose;
```

## Verification Checklist

- [ ] Approval chain exists for `stock_release` module
- [ ] `requesting_department` column exists in `stock_release_requests` table
- [ ] All pending release requests have corresponding approval requests
- [ ] GM can see pending release requests in dashboard
- [ ] GM can approve/reject release requests
- [ ] Stock is properly deducted when approved
- [ ] Requester receives notification of approval/rejection

## Common Issues

### Issue: "No approval chain configured for module: stock_release"
**Solution:** Run the fix SQL script to add the approval chain.

### Issue: Release requests not showing for GM
**Solution:** 
1. Check if approval_requests exist for the release requests
2. Verify the current_step is set to 1 (GM step)
3. Run the fix script to create missing approval requests

### Issue: Column 'requesting_department' doesn't exist
**Solution:** Run the fix SQL script to add the column.

### Issue: Stock not deducted after approval
**Solution:** Check the `ApprovalModel::_syncApproval()` method for the `stock_release` case. Ensure it's calling `InventoryModel::addMovement()` correctly.

## Files Created

1. `database/fix_inventory_approval_workflow.sql` - Complete fix script
2. `public/check_inventory_approval.php` - Diagnostic tool
3. `public/fix_inventory_approvals.php` - Web-based fix interface
4. `INVENTORY_APPROVAL_FIX.md` - This documentation

## Next Steps

After applying the fix:

1. Test the complete workflow with a new release request
2. Verify existing pending requests now appear for GM
3. Monitor the approval audit log for any issues
4. Consider adding automated tests for the approval workflow

## Support

If issues persist after applying this fix:

1. Check the `approval_audit_log` table for error messages
2. Review PHP error logs for exceptions
3. Verify user roles are correctly set (GM user must have role='gm')
4. Ensure the ApprovalModel is properly handling the stock_release case

## Date
May 6, 2026
