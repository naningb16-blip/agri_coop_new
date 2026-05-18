# Stock Return Approval System - Fixed

## Problem
GM could not approve stock returns in the Approvals section. The system was missing:
1. Approval chain for `stock_return` module
2. Sync logic in `ApprovalModel._syncApproval()` to handle approved returns
3. Rejection logic in `ApprovalModel._syncRejection()` to handle rejected returns

## Solution

### 1. Created Approval Chain Setup Script
**File:** `public/fix_stock_return_gm_approval.php`

This script:
- Creates GM-only approval chain for stock returns
- Checks existing pending returns
- Validates approval request linkage

**Run this script on the server to set up the approval chain.**

### 2. Added Stock Return Sync Logic
**File:** `app/models/ApprovalModel.php`

Added `stock_return` case to `_syncApproval()` method:
- When GM approves a stock return:
  - If condition is `good` → automatically restocks to inventory
  - If condition is `damaged` or `expired` → marks as disposed (no inventory movement)
  - Updates stock return status to `restocked` or `disposed`
  - Records the GM as reviewer

Added `stock_return` case to `_syncRejection()` method:
- When GM rejects a stock return:
  - Updates status to `rejected`
  - No inventory changes

## How It Works Now

### Stock Return Flow
1. **User creates stock return** in Inventory section
   - Selects product, warehouse, quantity
   - Chooses condition: Good, Damaged, or Expired
   - Provides reason
   - System creates approval request for GM

2. **GM reviews in Approvals section**
   - Sees stock return request with all details
   - Can approve or reject

3. **On Approval:**
   - **Good condition** → Stock automatically added back to inventory
   - **Damaged/Expired** → Marked as disposed, no inventory change
   - Stock return status updated
   - Requester notified

4. **On Rejection:**
   - Stock return marked as rejected
   - No inventory changes
   - Requester notified

## Damage/Defect Reporting

The system handles damage and defect reporting through the stock returns feature:

### Condition Types
- **Good** - Item is in good condition, will be restocked
- **Damaged** - Item is damaged, will be disposed
- **Expired** - Item is expired, will be disposed

### Usage
When reporting damaged or defective items:
1. Go to Inventory → Returns tab
2. Click "Create Return"
3. Select the product and warehouse
4. Choose condition type: "Damaged"
5. Provide reason explaining the damage
6. Submit for GM approval

GM will see the condition type and reason in the approval request and can approve disposal.

## Files Modified
1. `app/models/ApprovalModel.php` - Added stock_return sync logic
2. `public/fix_stock_return_gm_approval.php` - New setup script

## Testing Steps
1. Run `fix_stock_return_gm_approval.php` on server
2. Log in as regular user
3. Go to Inventory → Returns tab
4. Create a new stock return with "Good" condition
5. Log in as GM
6. Go to Approvals section
7. Approve the stock return
8. Verify stock was added back to inventory
9. Repeat with "Damaged" condition
10. Verify it's marked as disposed (no inventory change)

## Database Changes
```sql
-- Approval chain for stock returns (GM only)
INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) 
VALUES ('stock_return', 1, 'gm', 'General Manager', 1);
```

## Notes
- Stock returns now follow the same approval pattern as withdrawals and expenses
- GM has full control over what gets restocked vs disposed
- The condition type determines automatic action on approval
- All actions are logged in approval audit trail
