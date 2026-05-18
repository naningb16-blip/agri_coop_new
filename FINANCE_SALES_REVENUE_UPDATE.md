# Finance Module - Sales Revenue Update

## Changes Made

### Issue
Client was confused because Finance had two revenue metrics:
1. **Sales Revenue** - from `sales_orders` table (all orders, including unpaid)
2. **Cash Received** - from `receipts` table (Finance's own receipts)

Client wanted Sales Revenue to reflect actual cash received from Sales department, not just orders.

### Solution

**Sales Revenue now comes from actual cash receipts created by Sales department.**

## What Changed

### 1. Finance Controller (`app/controllers/FinanceController.php`)

**Before:**
```php
$salesRevenue = (float)($this->db->fetchOne(
    "SELECT COALESCE(SUM(total_amount),0) AS t FROM sales_orders 
     WHERE order_date BETWEEN ? AND ? AND status!='cancelled'",
    [$from, $to], 'ss'
)['t'] ?? 0);

$revenue = (float)($this->db->fetchOne(
    "SELECT COALESCE(SUM(amount),0) AS t FROM receipts 
     WHERE receipt_date BETWEEN ? AND ?",
    [$from, $to], 'ss'
)['t'] ?? 0);
```

**After:**
```php
// Sales Revenue = Cash received from Sales department only
$salesRevenue = (float)($this->db->fetchOne(
    "SELECT COALESCE(SUM(amount),0) AS t FROM receipts 
     WHERE receipt_date BETWEEN ? AND ? AND reference_type='sale'",
    [$from, $to], 'ss'
)['t'] ?? 0);

// Removed: $revenue variable (Cash Received)
```

### 2. Finance View (`app/views/finance/index.php`)

**Removed:**
- ❌ "Cash Received" stat card
- ❌ "Cash Receipts" tab from navigation
- ❌ Entire receipts tab content (table, modal, form)

**Updated:**
- ✅ "Sales Revenue" card now labeled "Sales Revenue (from Sales)"
- ✅ Shows only cash actually received from Sales department
- ✅ Simplified to 5 tabs: Overview, Expenses, Payroll, Purchases, Journal

## Data Flow

### Current Flow (After Changes)

```
Sales Department:
1. Creates sales order
2. Customer pays
3. Sales creates receipt (stored in receipts table with reference_type='sale')
   ↓
Finance Department:
4. Sees "Sales Revenue" = SUM of receipts where reference_type='sale'
5. No ability to create own receipts
6. Only manages: Expenses, Payroll, Purchases, Journal
```

### What Finance Sees Now

**Sales Revenue Card:**
- Source: `receipts` table where `reference_type='sale'`
- Represents: Actual cash received from customers via Sales dept
- Excludes: Unpaid orders, pending invoices

**Other Metrics (Unchanged):**
- Expenses: Approved expenses
- Payroll: Approved/paid payroll
- Purchases: Approved/delivered purchase orders
- Net Income: Sales Revenue - Total Costs

## Benefits

✅ **Clear Separation** - Sales handles all customer receipts  
✅ **Accurate Revenue** - Based on actual cash received, not just orders  
✅ **No Duplication** - Finance can't create conflicting receipts  
✅ **Simplified Finance** - Focus on expenses, payroll, purchases  
✅ **Better Accounting** - Revenue recognition matches cash basis  

## Migration Notes

**No database changes required** - Only logic and UI changes.

The `receipts` table already has `reference_type` column that distinguishes:
- `'sale'` - Receipts from Sales department
- `'purchase'` - Receipts from purchases
- `'payroll'` - Payroll-related receipts
- `'other'` - Other receipts

## Testing

1. **Sales Department:**
   - Create a sales order
   - Record payment and generate receipt
   - Verify receipt is created with `reference_type='sale'`

2. **Finance Department:**
   - Go to Finance module
   - Check "Sales Revenue" card shows the amount
   - Verify "Cash Receipts" tab is gone
   - Confirm can't create new receipts

3. **Reports:**
   - Check that financial reports use correct revenue source
   - Verify Net Income calculation is accurate

## Files Modified

1. `app/controllers/FinanceController.php`
   - Updated `_summary()` method
   - Removed `$revenue` variable
   - Changed `salesRevenue` to query receipts with `reference_type='sale'`

2. `app/views/finance/index.php`
   - Removed "Cash Received" stat card
   - Removed "Cash Receipts" tab
   - Updated "Sales Revenue" label
   - Removed receipts table and modal (lines 187-318)

## Rollback

If you need to revert these changes:

1. Restore `app/controllers/FinanceController.php` from git
2. Restore `app/views/finance/index.php` from git
3. Sales Revenue will go back to showing sales_orders total

## Notes

- Finance can still VIEW all receipts in the Journal tab
- Sales department retains full receipt generation capability
- This aligns with proper accounting separation of duties
- Revenue is now on cash basis (when received) not accrual basis (when ordered)

---
**Date:** May 6, 2026  
**Status:** Completed  
**Impact:** Finance module only - no database changes
