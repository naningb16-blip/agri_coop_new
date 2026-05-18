# Finance Department Enhancements Guide

## Overview

This document outlines the enhancements made to the Finance Department to improve tracking of sales receipts, purchase orders, and categorized billing expenses.

## Features Implemented

### 1. Sales Cash Receipts → Finance ✅ ALREADY WORKING

**Status**: Fully implemented (from previous work)

**How it works**:
- When Sales department creates a sales order with payment
- Cash receipts are automatically generated
- Receipts appear in Finance department's "Cash Receipts" tab
- Journal entries are created automatically
- Payment status tracked (Unpaid → Partial → Paid)

**No action needed** - This feature is already functional!

---

### 2. Purchase Orders → Finance 🆕 ENHANCED

**Status**: Database enhanced, UI update needed

**What's new**:
- Purchase Orders now visible in Finance department
- Finance can track PO amounts for budgeting
- Shows PO status (Pending, Approved, Delivered)
- Helps Finance prepare for upcoming payments

**Database Changes**:
- Added indexes for faster PO queries
- POs linked to Finance view

**Next Steps**:
- Add "Purchase Orders" section in Finance view
- Show PO amounts and payment status
- Track which POs need payment

---

### 3. Categorized Monthly Billing Expenses 🆕 NEW FEATURE

**Status**: Database ready, UI update needed

**Categories Added**:
1. **Utilities - Electric** (`utilities_electric`)
2. **Utilities - Water** (`utilities_water`)
3. **Utilities - Internet** (`utilities_internet`)
4. **Utilities - Phone** (`utilities_phone`)
5. **Rent** (`rent`)
6. **Office Supplies** (`supplies`)
7. **Maintenance** (`maintenance`)
8. **Transportation** (`transportation`)
9. **Professional Fees** (`professional_fees`)
10. **Insurance** (`insurance`)
11. **Taxes** (`taxes`)
12. **Salaries** (`salaries`)
13. **Other** (`other`)

**New Fields Added**:
- `billing_month` - Track which month the bill is for (YYYY-MM format)
- `due_date` - When the bill is due
- `vendor_name` - Who to pay (e.g., "Manila Electric Company")
- `account_number` - Account/reference number

**Benefits**:
- Track monthly recurring bills
- See expense breakdown by category
- Monitor utility costs over time
- Identify cost-saving opportunities

---

## Database Migration

Run this command to apply the enhancements:

```bash
mysql -u root -p agri_coop < database/finance_enhancements.sql
```

**What it does**:
1. Updates expense categories with billing types
2. Adds billing month and due date tracking
3. Adds vendor and account number fields
4. Creates indexes for faster queries
5. Shows sample data and verification

---

## How to Use

### For Finance Users

#### Creating a Monthly Bill Expense

1. Go to **Finance** → **Expenses** tab
2. Click **"Add Expense"**
3. Fill in the form:
   - **Category**: Select bill type (Electric, Water, etc.)
   - **Amount**: Bill amount
   - **Expense Date**: Date of expense
   - **Billing Month**: Which month is this bill for? (e.g., 2026-05)
   - **Due Date**: When is payment due?
   - **Vendor Name**: Who to pay (e.g., "Meralco")
   - **Account Number**: Your account/reference number
   - **Description**: Additional notes
4. Click **"Submit"**
5. Expense goes to approval workflow

#### Viewing Purchase Orders

1. Go to **Finance** → **Purchases** tab
2. See all approved purchase orders
3. Track amounts that need to be paid
4. View PO status:
   - **Approved**: Awaiting delivery
   - **Delivered**: Ready for payment
   - **Paid**: Payment completed

#### Viewing Sales Receipts

1. Go to **Finance** → **Cash Receipts** tab
2. See all receipts from Sales department
3. Filter by date range
4. View payment methods (Cash/Charge/Credit)
5. Track total revenue collected

---

## Monthly Billing Workflow

### Example: Electric Bill

1. **Receive Bill**
   - Electric company sends bill for May 2026
   - Amount: ₱15,000.00
   - Due date: June 10, 2026

2. **Create Expense**
   - Category: `Utilities - Electric`
   - Amount: 15000.00
   - Billing Month: `2026-05`
   - Due Date: `2026-06-10`
   - Vendor: `Manila Electric Company`
   - Account Number: `1234-5678-9012`

3. **Approval**
   - Expense goes through approval workflow
   - Manager reviews and approves
   - GM gives final approval

4. **Payment**
   - Once approved, Finance processes payment
   - Journal entry created automatically
   - Expense marked as paid

5. **Tracking**
   - View all May 2026 bills
   - Compare with previous months
   - Analyze utility costs

---

## Reports Available

### Expense by Category Report

Shows breakdown of expenses by category:
- How much spent on utilities
- How much on rent
- How much on supplies
- Etc.

### Monthly Billing Report

Shows all bills for a specific month:
- All May 2026 bills
- Total amount due
- Payment status
- Due dates

### Purchase Order Tracking

Shows all POs and their payment status:
- Approved POs awaiting delivery
- Delivered POs awaiting payment
- Total amount committed

---

## Integration Points

### Sales → Finance
- **Trigger**: Sales order with payment created
- **Action**: Receipt automatically appears in Finance
- **Data**: Receipt number, amount, payment method, customer
- **Status**: ✅ Working

### Purchasing → Finance
- **Trigger**: Purchase order approved
- **Action**: PO appears in Finance purchases tab
- **Data**: PO number, supplier, amount, status
- **Status**: 🔧 Database ready, UI update needed

### Expenses → Journal
- **Trigger**: Expense approved
- **Action**: Journal entry created automatically
- **Data**: Debit/Credit accounts, amount, date
- **Status**: ✅ Working

---

## UI Updates Needed

### Finance Index View (`app/views/finance/index.php`)

#### Expenses Tab Enhancement
Add fields to expense creation form:
```php
<select name="category" required>
    <option value="utilities_electric">Electric Bill</option>
    <option value="utilities_water">Water Bill</option>
    <option value="utilities_internet">Internet Bill</option>
    <option value="utilities_phone">Phone Bill</option>
    <option value="rent">Rent</option>
    <option value="supplies">Office Supplies</option>
    <option value="maintenance">Maintenance</option>
    <option value="transportation">Transportation</option>
    <option value="professional_fees">Professional Fees</option>
    <option value="insurance">Insurance</option>
    <option value="taxes">Taxes</option>
    <option value="salaries">Salaries</option>
    <option value="other">Other</option>
</select>

<input type="month" name="billing_month" placeholder="Billing Month (YYYY-MM)">
<input type="date" name="due_date" placeholder="Due Date">
<input type="text" name="vendor_name" placeholder="Vendor Name">
<input type="text" name="account_number" placeholder="Account Number">
```

#### Purchases Tab Enhancement
Show purchase orders:
```php
<table>
    <thead>
        <tr>
            <th>PO Number</th>
            <th>Date</th>
            <th>Supplier</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($purchaseOrders as $po): ?>
        <tr>
            <td><?= $po['po_number'] ?></td>
            <td><?= date('M d, Y', strtotime($po['order_date'])) ?></td>
            <td><?= $po['supplier_name'] ?></td>
            <td>₱<?= number_format($po['total_amount'], 2) ?></td>
            <td><span class="badge"><?= $po['status'] ?></span></td>
            <td><a href="/purchasing/po-detail?id=<?= $po['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### Finance Controller (`app/controllers/FinanceController.php`)

#### Update createExpense method
Add new fields:
```php
public function createExpense(): void {
    // ... existing code ...
    $billingMonth = $_POST['billing_month'] ?? null;
    $dueDate      = $_POST['due_date'] ?? null;
    $vendorName   = trim($_POST['vendor_name'] ?? '');
    $accountNum   = trim($_POST['account_number'] ?? '');
    
    $id = $this->db->insert(
        "INSERT INTO expenses 
         (category, description, amount, expense_date, billing_month, due_date, vendor_name, account_number, status, created_by)
         VALUES (?,?,?,?,?,?,?,?,'pending',?)",
        [$cat, $desc, $amount, $date, $billingMonth, $dueDate, $vendorName, $accountNum, $_SESSION['user_id']],
        'ssdsssssi'
    );
    // ... rest of code ...
}
```

#### Add getPurchaseOrders method
```php
private function _purchases($from, $to): array {
    return $this->db->fetchAll(
        "SELECT po.*, s.name AS supplier_name
         FROM purchase_orders po
         JOIN suppliers s ON po.supplier_id = s.id
         WHERE po.order_date BETWEEN ? AND ?
         AND po.status IN ('approved', 'delivered')
         ORDER BY po.order_date DESC",
        [$from, $to], 'ss'
    );
}
```

---

## Testing Checklist

### Sales Receipts
- [ ] Create sales order with cash payment
- [ ] Verify receipt appears in Finance → Cash Receipts
- [ ] Check receipt number format (REC-YYYYMMDD-XXXX)
- [ ] Verify amount matches
- [ ] Check journal entry created

### Purchase Orders
- [ ] Create and approve a purchase order
- [ ] Verify PO appears in Finance → Purchases
- [ ] Check PO amount displayed correctly
- [ ] Verify status shows correctly
- [ ] Test filtering by date range

### Billing Expenses
- [ ] Create electric bill expense
- [ ] Select "Utilities - Electric" category
- [ ] Enter billing month (e.g., 2026-05)
- [ ] Enter due date
- [ ] Enter vendor name (e.g., "Meralco")
- [ ] Enter account number
- [ ] Submit for approval
- [ ] Verify appears in Expenses tab
- [ ] Check category filter works
- [ ] Approve expense
- [ ] Verify journal entry created

---

## Benefits

### For Finance Department
✅ **Complete Visibility**: See all financial transactions in one place  
✅ **Better Tracking**: Track bills by category and month  
✅ **Improved Planning**: See upcoming PO payments  
✅ **Automated Recording**: Sales receipts auto-appear  
✅ **Organized Expenses**: Categorized billing makes reporting easier  

### For Management
✅ **Cost Analysis**: See where money is being spent  
✅ **Budget Control**: Track expenses by category  
✅ **Cash Flow**: See upcoming payments (POs + bills)  
✅ **Audit Trail**: Complete transaction history  

### For Auditors
✅ **Clear Categories**: Expenses properly classified  
✅ **Complete Records**: All transactions tracked  
✅ **Journal Entries**: Automatic accounting entries  
✅ **Approval Trail**: Who approved what and when  

---

## Summary

### What's Working Now
✅ Sales cash receipts → Finance (automatic)  
✅ Expense approval workflow  
✅ Journal entry automation  

### What's Enhanced
🆕 Purchase orders visible in Finance  
🆕 Categorized billing expenses  
🆕 Monthly bill tracking  
🆕 Vendor and account number tracking  

### Next Steps
1. Run database migration
2. Update Finance UI with new fields
3. Test all features
4. Train Finance users
5. Monitor and adjust categories as needed

---

## Support

For questions or issues:
1. Check this guide first
2. Verify database migration ran successfully
3. Test with sample data
4. Review error logs if issues occur

**Status**: Database ready, UI updates in progress
