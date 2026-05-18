# Fix Stock Return GM Approval - Step by Step Instructions

## Problem
GM cannot see Approve/Reject buttons when viewing stock return approval requests.

## Root Cause
One or more of these issues:
1. Approval chain not configured for stock_return module
2. Approval requests not created for existing stock returns
3. Render deployment issue (code fix exists but not deployed to live server)

## Solution - Follow These Steps

### Step 1: Run the Test Page
**URL:** `https://your-domain.com/test_gm_stock_return_approval.php`

This page will:
- Check if you're logged in as GM
- Verify approval chain exists
- Show all pending stock returns
- Tell you exactly what needs to be done

### Step 2: Fix Issues Based on Test Results

#### If "Approval Chain Missing"
1. Click the "Run Setup Script" button
2. Or go to: `https://your-domain.com/fix_stock_return_gm_approval.php`
3. This creates the GM approval chain for stock returns

#### If "Some Stock Returns Missing Approval Requests"
1. Click the "Create Missing Approvals" button
2. Or go to: `https://your-domain.com/create_missing_stock_return_approvals.php`
3. This creates approval requests for all pending stock returns

### Step 3: Test the Approval
1. Go to: Approvals section in the main menu
2. Look for stock return approval requests
3. Click on one to view details
4. You should see the "Take Action" panel with Approve/Reject buttons

### Step 4: If You Still Don't See Buttons

This means Render hasn't deployed the code fix. The fix exists in the code but the live server isn't serving it.

**The Code Fix:**
- File: `app/controllers/ApprovalController.php` (line 54)
- Changed from: `$canAct = ($user['role'] === 'admin' || $user['role'] === $s['approver_role']);`
- Changed to: `$canAct = in_array($user['role'], ['admin', 'gm', 'manager']) || $user['role'] === $s['approver_role'];`

**Solutions:**
1. Contact Render support about deployment not working
2. Recreate the Render deployment service
3. Wait for Render to fix their deployment pipeline
4. Switch to a different hosting provider

## Quick Reference

### Diagnostic Tools
- **Test Page:** `test_gm_stock_return_approval.php` - Simple step-by-step test
- **Debug Page:** `debug_stock_return_approval.php` - Detailed technical debug info
- **System Status:** `system_status_check.php` - Overall system status

### Setup Scripts
- **Setup Approval Chain:** `fix_stock_return_gm_approval.php`
- **Create Approval Requests:** `create_missing_stock_return_approvals.php`

## Expected Behavior After Fix

1. **GM logs in** → Goes to Approvals section
2. **Sees stock return requests** → Clicks on one
3. **Views approval detail page** → Sees "Take Action" panel
4. **Can approve or reject** → Clicks Approve/Reject button
5. **System processes approval:**
   - If approved + good condition → Stock is restocked automatically
   - If approved + damaged/defective → Marked as disposed
   - If rejected → Stock return is rejected

## Verification Checklist

- [ ] Logged in as GM user
- [ ] Approval chain exists for stock_return module
- [ ] Pending stock returns have approval requests
- [ ] Can see approval requests in Approvals section
- [ ] Can click on approval request to view details
- [ ] Can see "Take Action" panel with Approve/Reject buttons
- [ ] Can successfully approve or reject

## Common Issues

### "I don't see any stock return approvals"
- No pending stock returns exist
- Create a stock return first from Inventory section

### "I see the approval but no buttons"
- Render deployment issue - code not deployed
- Run the test page to confirm
- Contact Render support

### "I'm not logged in as GM"
- Login with a GM user account
- Or ask admin to change your role to 'gm'

### "Approval chain missing"
- Run `fix_stock_return_gm_approval.php`

### "Approval requests missing"
- Run `create_missing_stock_return_approvals.php`

## Technical Details

### Database Tables Involved
- `approval_chains` - Defines who approves what
- `approval_requests` - Individual approval requests
- `approval_steps` - Steps in each approval request
- `stock_returns` - The actual stock return records

### Permission Logic
```php
// GM, manager, and admin can all approve
$canAct = in_array($user['role'], ['admin', 'gm', 'manager']) 
    || $user['role'] === $s['approver_role'];
```

### Approval Flow
1. Stock return created → status: 'pending'
2. Approval request created → module: 'stock_return'
3. Approval step created → approver_role: 'gm', status: 'pending'
4. GM approves → step status: 'approved'
5. System processes:
   - Good condition → Creates inventory movement (type: 'return')
   - Damaged → Updates stock_return status to 'disposed'

## Support

If you're still having issues after following these steps:
1. Run `test_gm_stock_return_approval.php` and share the results
2. Run `debug_stock_return_approval.php?request_id=X` (replace X with approval request ID)
3. Check if it's a Render deployment issue
4. Verify the code fix exists in `app/controllers/ApprovalController.php` line 54

---

**Last Updated:** May 19, 2026
