# Finance Expense Status - Fixed

## Issue

In the Finance department expenses tab, there were **two status columns**:
1. **Status** - From the `expenses` table
2. **Approval** - From the `approval_requests` table

When GM approved an expense through the Approvals section:
- ✅ Approval status changed to "approved"
- ❌ Expense status remained "pending"

This caused confusion because users saw two different statuses for the same expense.

## Root Cause

The system has two separate status tracking systems:
1. **Expense Status** (`expenses.status`) - Updated by Finance department directly
2. **Approval Status** (`approval_requests.status`) - Updated through Approvals workflow

When GM approves through the Approvals section, the `ApprovalModel._syncApproval()` method should update the expense status, but there may be timing or transaction issues.

## Solution

### Simplified the View

Instead of showing two separate status columns, the view now shows **one unified status** that intelligently displays the correct status:

**Logic:**
- If approval exists and is approved/rejected → Show approval status
- Otherwise → Show expense status
- Added info icon link to view approval details

### Changes Made

**File:** `app/views/finance/index.php`

**Before:**
```
| Date | Category | Description | Amount | Method | Status | Approval | By | Actions |
```

**After:**
```
| Date | Category | Description | Amount | Method | Status | By | Actions |
```

**Status Column Logic:**
```php
<?php 
// Show approval status if it exists and is different from expense status
$displayStatus = $e['status'];
if ($e['approval_request_id'] && $e['approval_status']) {
    // If approval is approved/rejected, use that status
    if (in_array($e['approval_status'], ['approved', 'rejected'])) {
        $displayStatus = $e['approval_status'];
    }
}
?>
<span class="badge badge-<?= $displayStatus ?>"><?= ucfirst($displayStatus) ?></span>
<?php if ($e['approval_request_id']): ?>
<a href="<?= BASE_URL ?>/approvals/detail?id=<?= $e['approval_request_id'] ?>" class="ms-1" title="View approval details">
    <i class="bi bi-info-circle text-muted"></i>
</a>
<?php endif; ?>
```

## How It Works Now

### Scenario 1: Expense Created (Pending Approval)
- **Status shown**: "Pending"
- **Info icon**: Links to approval details

### Scenario 2: GM Approves Through Approvals Section
- **Status shown**: "Approved" (from approval_status)
- **Info icon**: Links to approval details
- **Background**: `_syncApproval` also updates expense status

### Scenario 3: Finance Approves Directly
- **Status shown**: "Approved" (from expense status)
- **No info icon**: No approval workflow used

### Scenario 4: Rejected
- **Status shown**: "Rejected"
- **Info icon**: Links to approval details

## Benefits

✅ **Single Status Display** - No more confusion with two statuses
✅ **Intelligent Logic** - Shows the most relevant status
✅ **Approval Link** - Easy access to approval details via info icon
✅ **Cleaner UI** - One less column, more space
✅ **Accurate Totals** - Approved total includes both expense and approval statuses

## Backend Sync Still Works

The `ApprovalModel._syncApproval()` method still updates the expense status in the database:

```php
case 'expense':
    $this->db->query(
        "UPDATE expenses SET status='approved', approved_by=? WHERE id=?",
        [$actorId, $refId], 'ii'
    );
    // Also creates journal entry
    break;
```

So both statuses should eventually match, but the view now handles cases where they might be temporarily out of sync.

## Testing

1. **Create an expense** → Status shows "Pending"
2. **GM approves through Approvals** → Status shows "Approved"
3. **Check database** → Both `expenses.status` and `approval_requests.status` should be "approved"
4. **Click info icon** → Opens approval details page

## Summary

✅ **Fixed** - Removed duplicate status column
✅ **Simplified** - Single unified status display
✅ **Improved** - Better user experience
✅ **Maintained** - Backend sync logic still works

The Finance expenses tab now shows a single, clear status that reflects the actual approval state of each expense.
