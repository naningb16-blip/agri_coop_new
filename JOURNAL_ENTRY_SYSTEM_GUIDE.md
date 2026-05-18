# Journal Entry System Guide

## Overview

The Agricultural Cooperative ERP uses an **automatic double-entry bookkeeping system** that creates journal entries whenever financial transactions occur. This ensures accurate financial records and maintains the accounting equation: **Assets = Liabilities + Equity**.

## How Journal Entries Work

### What is a Journal Entry?

A journal entry records every financial transaction with two sides:
- **Debit Account**: The account receiving value (money coming in or assets increasing)
- **Credit Account**: The account giving value (money going out or liabilities increasing)

Every transaction is automatically recorded in the `journal_entries` table with:
- Entry date
- Reference number (e.g., REC-20260505-A1B2)
- Description
- Debit and credit accounts
- Amount
- Source type and ID (links back to original transaction)
- Created by (user who initiated)

### Database Structure

```sql
CREATE TABLE journal_entries (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    entry_date     DATE NOT NULL,
    reference      VARCHAR(100),
    description    VARCHAR(255) NOT NULL,
    debit_account  VARCHAR(100) NOT NULL,
    credit_account VARCHAR(100) NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    source_type    VARCHAR(100),  -- receipt, expense, payroll, purchase
    source_id      INT,            -- Links to original transaction
    created_by     INT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Automatic Journal Entry Creation

Journal entries are created automatically by the `FinanceController::_journal()` method when specific financial events occur.

### 1. Cash Receipts & Charge Invoices

**When**: A receipt is created in Finance → Receipts tab  
**Triggered by**: `FinanceController::createReceipt()` (Line 73)

**Example Transaction**:
```
Customer pays ₱5,000 cash for a sale
```

**Journal Entry Created**:
```
Entry Date:     2026-05-05
Reference:      REC-20260505-A1B2
Description:    Cash Receipt: Juan Dela Cruz — Rice Sale
Debit Account:  Cash
Credit Account: Accounts Receivable
Amount:         ₱5,000.00
Source Type:    receipt
Source ID:      123
```

**Accounting Logic**:
- **Debit Cash** (asset increases) when payment method is "cash"
- **Debit Bank** (asset increases) when payment method is "bank_transfer" or "check"
- **Credit Accounts Receivable** (liability decreases) for sales
- **Credit Other Income** for non-sales receipts

---

### 2. Expenses

**When**: An expense is approved by Finance Manager/GM  
**Triggered by**: `FinanceController::approveExpense()` (Line 127)

**Example Transaction**:
```
Electric bill of ₱3,500 is approved
```

**Journal Entry Created**:
```
Entry Date:     2026-05-05
Reference:      EXP-45
Description:    Expense approved: utilities_electric — May 2026 Electric Bill
Debit Account:  utilities_electric
Credit Account: Cash
Amount:         ₱3,500.00
Source Type:    expense
Source ID:      45
```

**Accounting Logic**:
- **Debit Expense Category** (expense increases) - uses the category from the expense form:
  - utilities_electric, utilities_water, utilities_internet, utilities_phone
  - rent, supplies, maintenance, transportation
  - professional_fees, insurance, taxes, salaries, other
- **Credit Cash** (asset decreases) - money paid out

**Important**: Journal entries are only created when expenses are **approved**, not when submitted.

---

### 3. Payroll

**When**: Payroll is approved by GM  
**Triggered by**: `FinanceController::approvePayroll()` (Line 177)

**Example Transaction**:
```
Employee payroll of ₱15,000 net pay is approved
```

**Journal Entry Created**:
```
Entry Date:     2026-05-15
Reference:      PAY-78
Description:    Payroll approved for employee #12
Debit Account:  Salaries Expense
Credit Account: Cash
Amount:         ₱15,000.00
Source Type:    payroll
Source ID:      78
```

**Accounting Logic**:
- **Debit Salaries Expense** (expense increases)
- **Credit Cash** (asset decreases) - salary paid to employee

**Bulk Approval**: When using "Approve All Payroll", journal entries are created for each employee individually.

---

### 4. Purchase Orders

**When**: Purchase orders are approved and delivered  
**Tracked in**: Finance → Purchases tab (read-only view)

**Note**: Purchase order journal entries are created by the Purchasing module, not Finance. The Finance module displays them for reporting purposes only.

---

## How to View Journal Entries

### In the Finance Module

1. Go to **Finance Department** → **Journal** tab
2. Select date range (From/To)
3. Click **Filter** button

### What You'll See

The journal view displays:
- **Date**: When the transaction occurred
- **Reference**: Unique transaction ID (REC-xxx, EXP-xxx, PAY-xxx)
- **Description**: What the transaction was for
- **Debit Account**: Account receiving value
- **Credit Account**: Account giving value
- **Amount**: Transaction amount in ₱
- **Source**: Type and ID of original transaction
- **Created By**: User who initiated the transaction

### Example Journal View

| Date       | Reference      | Description                    | Debit Account        | Credit Account       | Amount     |
|------------|----------------|--------------------------------|----------------------|----------------------|------------|
| 2026-05-05 | REC-20260505-A1B2 | Cash Receipt: Juan Dela Cruz | Cash                 | Accounts Receivable  | ₱5,000.00  |
| 2026-05-05 | EXP-45         | Expense: Electric Bill         | utilities_electric   | Cash                 | ₱3,500.00  |
| 2026-05-15 | PAY-78         | Payroll for employee #12       | Salaries Expense     | Cash                 | ₱15,000.00 |

---

## Double-Entry Bookkeeping Principles

### The Accounting Equation

```
Assets = Liabilities + Equity
```

Every transaction affects at least two accounts to keep this equation balanced.

### Account Types

1. **Assets** (what you own):
   - Cash, Bank, Accounts Receivable, Inventory

2. **Liabilities** (what you owe):
   - Accounts Payable, Loans

3. **Equity** (owner's stake):
   - Capital, Retained Earnings

4. **Revenue** (income):
   - Sales Revenue, Other Income

5. **Expenses** (costs):
   - Salaries, Utilities, Rent, Supplies, etc.

### Debit and Credit Rules

| Account Type | Increase | Decrease |
|--------------|----------|----------|
| Assets       | Debit    | Credit   |
| Liabilities  | Credit   | Debit    |
| Equity       | Credit   | Debit    |
| Revenue      | Credit   | Debit    |
| Expenses     | Debit    | Credit   |

---

## Transaction Examples

### Example 1: Customer Pays Cash for Sale

```
Transaction: Customer pays ₱10,000 cash
Journal Entry:
  Debit:  Cash                    ₱10,000
  Credit: Accounts Receivable     ₱10,000
```

**Effect**: Cash (asset) increases, Accounts Receivable (asset) decreases

---

### Example 2: Pay Electric Bill

```
Transaction: Pay ₱3,500 for electricity
Journal Entry:
  Debit:  Utilities Electric      ₱3,500
  Credit: Cash                    ₱3,500
```

**Effect**: Expense increases, Cash (asset) decreases

---

### Example 3: Pay Employee Salary

```
Transaction: Pay ₱15,000 salary
Journal Entry:
  Debit:  Salaries Expense        ₱15,000
  Credit: Cash                    ₱15,000
```

**Effect**: Expense increases, Cash (asset) decreases

---

## Technical Implementation

### The `_journal()` Method

Located in `app/controllers/FinanceController.php`, this method has dual functionality:

#### 1. Insert Mode (when called with array)

```php
$this->_journal([
    'entry_date'     => '2026-05-05',
    'reference'      => 'REC-20260505-A1B2',
    'description'    => 'Cash Receipt: Customer Name',
    'debit_account'  => 'Cash',
    'credit_account' => 'Accounts Receivable',
    'amount'         => 5000.00,
    'source_type'    => 'receipt',
    'source_id'      => 123,
]);
```

#### 2. Retrieval Mode (when called with date range)

```php
$journal = $this->_journal('2026-05-01', '2026-05-31');
// Returns array of journal entries for the date range
```

### Where Journal Entries Are Created

| Controller Method | Line | Transaction Type |
|-------------------|------|------------------|
| `FinanceController::createReceipt()` | 73 | Cash receipts & charge invoices |
| `FinanceController::approveExpense()` | 127 | Approved expenses |
| `FinanceController::approvePayroll()` | 177 | Approved payroll (single) |
| `FinanceController::approveAllPayroll()` | 220 | Approved payroll (bulk) |

---

## Frequently Asked Questions

### Q: When are journal entries created?

**A**: Journal entries are created automatically when:
- A receipt is recorded (immediately)
- An expense is **approved** (not when submitted)
- Payroll is **approved** by GM (not when created)
- Purchase orders are approved (by Purchasing module)

### Q: Can I manually create journal entries?

**A**: No. Journal entries are automatically created by the system to ensure accuracy and prevent errors. Manual entries would break the audit trail.

### Q: Can I edit or delete journal entries?

**A**: No. Journal entries are immutable (cannot be changed) to maintain financial integrity. If a transaction was recorded incorrectly, you must create a correcting entry.

### Q: How do I see all transactions for a specific account?

**A**: Use the Journal tab with date filters. You can search for specific accounts in the Debit Account or Credit Account columns.

### Q: What if I need to reverse a transaction?

**A**: Create a new transaction with the opposite debit/credit accounts. For example, if you recorded a ₱5,000 receipt incorrectly, create a new receipt with negative amount or contact your system administrator.

### Q: Where can I see the total balance for each account?

**A**: The current system shows individual journal entries. For account balances and trial balance reports, these would need to be added as a future enhancement.

---

## Summary

The journal entry system provides:

✅ **Automatic recording** of all financial transactions  
✅ **Double-entry bookkeeping** for accuracy  
✅ **Audit trail** with source references  
✅ **Real-time financial data** for reporting  
✅ **Immutable records** for compliance  

All financial transactions in the system (receipts, expenses, payroll, purchases) are automatically recorded in the journal, ensuring complete and accurate financial records without manual data entry.

---

**Last Updated**: May 5, 2026  
**System Version**: Agricultural Cooperative ERP v1.0
