# Purchase Order Accounting Flow

## Overview
This document explains how purchase orders create journal entries for proper accounting of inventory purchases and accounts payable.

## Automatic Journal Entry Creation

### When Supplier Invoice Provided at PO Creation

**Trigger**: User enters supplier invoice number when creating PO

**Automatic Actions**:
1. PO created with supplier invoice number
2. Payment status set to "unpaid"
3. Invoice date set to current date
4. **Journal entry automatically created**:
   ```
   Date: [Current Date]
   Reference: [Supplier Invoice Number]
   Description: Accounts Payable for PO [PO-NUMBER] - Supplier Invoice: [INV-NUMBER] - [Supplier Name]
   
   Debit:  Inventory           ₱X,XXX.XX
   Credit: Accounts Payable    ₱X,XXX.XX
   ```

**Result**: 
- Inventory asset increased
- Accounts Payable liability increased
- Financial statements immediately reflect the purchase
- Outstanding payables visible in reports

### When Supplier Invoice NOT Provided at PO Creation

**Trigger**: User creates PO without supplier invoice number

**Actions**:
1. PO created normally
2. No journal entry created yet
3. Payment status remains null
4. Can add invoice later using "Record Supplier Invoice" button

**Later - When Invoice Recorded**:
- User clicks "Record Supplier Invoice" in PO detail page
- Enters invoice number, date, payment terms
- System creates journal entry at that time
- Same accounting result, just delayed

## Complete Purchase Cycle with Journal Entries

### Step 1: Create PO with Supplier Invoice
```
Journal Entry #1 (Automatic):
Date: 2026-05-07
Reference: SI-2026-001
Description: Accounts Payable for PO-20260507-A1B2 - Supplier Invoice: SI-2026-001 - ABC Supplies

Debit:  Inventory           ₱50,000.00
Credit: Accounts Payable    ₱50,000.00

Effect:
- Inventory: +₱50,000
- Accounts Payable: +₱50,000
- Cash: No change
```

### Step 2: PO Approved by GM
- No journal entry
- Just approval workflow update
- PO status changes to "approved"

### Step 3: PO Delivered (Goods Received)
- Inventory physically received
- Stock-in to warehouse (inventory movement)
- No additional journal entry (already recorded in Step 1)
- PO status changes to "delivered"

### Step 4: Payment Made to Supplier
```
Journal Entry #2 (Manual via "Record Payment"):
Date: 2026-05-15
Reference: PO-20260507-A1B2-PAY
Description: Payment for PO PO-20260507-A1B2

Debit:  Accounts Payable    ₱50,000.00
Credit: Bank                ₱50,000.00

Effect:
- Accounts Payable: -₱50,000 (cleared)
- Bank: -₱50,000
- Inventory: No change (already recorded)
```

### Final Result
- Inventory: +₱50,000 (asset increased)
- Cash/Bank: -₱50,000 (asset decreased)
- Accounts Payable: ₱0 (liability cleared)
- **Net Effect**: Exchanged cash for inventory

## Alternative Flow: Invoice Added Later

### Step 1: Create PO WITHOUT Supplier Invoice
- PO created
- No journal entry yet
- Payment status: null

### Step 2: PO Approved & Delivered
- Goods received
- Stock-in to warehouse
- Still no journal entry

### Step 3: Record Supplier Invoice (Later)
```
Journal Entry #1 (Manual via "Record Supplier Invoice"):
Date: 2026-05-10
Reference: PO-1-INV
Description: Supplier Invoice: SI-2026-001 for PO PO-20260507-A1B2

Debit:  Inventory           ₱50,000.00
Credit: Accounts Payable    ₱50,000.00
```

### Step 4: Payment Made
```
Journal Entry #2:
Debit:  Accounts Payable    ₱50,000.00
Credit: Bank                ₱50,000.00
```

**Same final result, just different timing**

## Partial Payments

### Example: ₱50,000 PO, Pay ₱20,000 First

**Payment 1**:
```
Debit:  Accounts Payable    ₱20,000.00
Credit: Bank                ₱20,000.00

Remaining Payable: ₱30,000
Payment Status: "partial"
```

**Payment 2**:
```
Debit:  Accounts Payable    ₱30,000.00
Credit: Bank                ₱30,000.00

Remaining Payable: ₱0
Payment Status: "paid"
```

## Account Types Used

### Debit Accounts (Assets/Expenses)
- **Inventory** - For goods purchased for resale or production
- **Purchases** - Alternative account (can be configured)

### Credit Accounts (Liabilities/Assets)
- **Accounts Payable** - Money owed to suppliers
- **Bank** - Bank account (when paying)
- **Cash** - Cash on hand (when paying cash)

## Reports Impact

### Balance Sheet
- **Assets**: Inventory increases when PO created with invoice
- **Liabilities**: Accounts Payable increases when PO created with invoice
- **Assets**: Cash/Bank decreases when payment made

### Income Statement
- No immediate impact (inventory is asset, not expense)
- Expense recorded when inventory sold (COGS)

### Accounts Payable Report
- Shows all outstanding payables
- Includes PO number, supplier, invoice number, amount, due date
- Updated in real-time as invoices recorded and payments made

### Cash Flow Statement
- Operating Activities: Cash paid to suppliers (when payment made)
- No impact when invoice recorded (non-cash transaction)

## Key Benefits

1. **Immediate Recording** - Liabilities recorded as soon as invoice received
2. **Accurate Reports** - Financial statements always current
3. **Payment Tracking** - Easy to see what's owed and when
4. **Audit Trail** - Complete history of all transactions
5. **Automated** - No manual journal entries needed for standard flow
6. **Flexible** - Works whether invoice provided upfront or later

## Database Tables

### journal_entries
- `entry_date` - Date of transaction
- `reference` - Supplier invoice number or payment reference
- `description` - Details of transaction
- `debit_account` - Account debited (Inventory)
- `credit_account` - Account credited (Accounts Payable or Bank)
- `amount` - Transaction amount
- `source_type` - 'purchase_order'
- `source_id` - PO ID
- `created_by` - User who created entry

### purchase_orders
- `supplier_invoice_number` - Supplier's invoice number
- `supplier_invoice_date` - Date of supplier's invoice
- `payment_status` - 'unpaid', 'partial', 'paid'
- `amount_paid` - Total amount paid so far
- `payment_terms` - e.g., "Net 30"
- `payment_due_date` - When payment is due

## Summary

The system automatically creates proper accounting entries when supplier invoices are provided, ensuring:
- Accurate financial records
- Proper liability tracking
- Complete audit trail
- Real-time financial reporting
- Simplified accounting workflow

All journal entries are linked to source documents (POs) for easy reference and auditing.
