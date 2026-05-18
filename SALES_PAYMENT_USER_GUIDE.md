# Sales Payment & Invoice User Guide

## For Sales Department Users

### Creating a Sales Order with Payment Type

1. **Navigate to Sales Department**
   - Click "Sales" in the main menu

2. **Create New Order**
   - Click "New Order" button
   - Fill in customer information
   - **Select Payment Type:**
     - **Cash**: Customer pays immediately upon delivery
     - **Charge**: Customer has account, will be invoiced
     - **Credit**: Customer pays on credit terms
   - Add items to the order
   - Submit order

3. **Order Approval**
   - Order goes through normal approval workflow
   - GM must approve before delivery

### Recording Payment After Delivery

1. **Open Order Details**
   - Click on the order number from the sales list
   - Order must be in "Delivered" status

2. **Record Payment Section**
   - Appears automatically for delivered orders with unpaid/partial balance
   - Shows current balance due

3. **Enter Payment Details**
   - **Amount**: Enter payment amount (can be partial)
   - **Payment Method**: Select Cash, Bank Transfer, or Check
   - **Payment Date**: Select date payment was received
   - **Notes**: Optional notes about the payment

4. **Submit Payment**
   - Click "Record Payment" button
   - System generates receipt automatically
   - Receipt appears in Finance department

### Payment Status Indicators

- **Unpaid** (Gray badge): No payment received yet
- **Partial** (Yellow badge): Some payment received, balance remaining
- **Paid** (Green badge): Fully paid

### Payment Type Badges

- **Cash** (Green): Cash payment
- **Charge** (Yellow): Charge invoice
- **Credit** (Blue): Credit terms

## For Finance Department Users

### Viewing Sales Receipts

1. **Navigate to Finance Department**
   - Click "Finance" in the main menu

2. **Go to Cash Receipts Tab**
   - Click "Cash Receipts" tab
   - All sales receipts appear here automatically

3. **Receipt Information Displayed**
   - Receipt Number (REC-YYYYMMDD-XXXX)
   - Receipt Type badge (Cash Receipt / Charge Invoice)
   - Payment Date
   - Customer Name
   - Item Description
   - Amount
   - Payment Method
   - Received By

4. **Print Receipt**
   - Click printer icon next to receipt
   - Opens printable receipt format
   - Can be given to customer

### Understanding Receipt Types

- **Cash Receipt** (Green badge): 
  - Generated for orders with payment_type = "Cash"
  - Customer paid at time of delivery or shortly after

- **Charge Invoice** (Yellow badge):
  - Generated for orders with payment_type = "Charge" or "Credit"
  - Customer has account and will pay later
  - Tracks accounts receivable

### Journal Entries

All sales receipts automatically create journal entries:
- View in Finance → Journal tab
- Shows debit/credit accounts
- Links back to original receipt
- Provides complete audit trail

## For General Manager

### View-Only Access

- GM can view all sales orders and payment information
- GM can see payment status and history
- GM **cannot** record payments (sales department function)
- GM can approve/reject orders through Approvals section

### Dashboard Summary

Sales dashboard shows:
- **Revenue**: Total sales value
- **Collected**: Money actually received
- **Outstanding**: Money still owed by customers

## Common Scenarios

### Scenario 1: Cash Sale
1. Create order with payment_type = "Cash"
2. Order approved and delivered
3. Record full payment immediately
4. Status changes to "Paid"
5. Cash receipt generated in Finance

### Scenario 2: Charge Account Customer
1. Create order with payment_type = "Charge"
2. Order approved and delivered
3. Customer takes delivery on account
4. Record payment when customer pays (can be days/weeks later)
5. Charge invoice generated in Finance

### Scenario 3: Partial Payments
1. Create order with any payment_type
2. Order approved and delivered
3. Customer pays 50% → Record first payment
4. Status changes to "Partial"
5. Customer pays remaining 50% → Record second payment
6. Status changes to "Paid"
7. Two separate receipts generated

### Scenario 4: Credit Terms (e.g., Net 30)
1. Create order with payment_type = "Credit"
2. Order approved and delivered
3. Customer has 30 days to pay
4. Record payment when received
5. Charge invoice tracks accounts receivable

## Tips & Best Practices

### For Sales Department
- ✅ Always select correct payment type when creating order
- ✅ Record payments promptly when received
- ✅ Add notes to payment records for clarity
- ✅ Verify payment amount before submitting
- ✅ Keep receipt numbers for customer reference

### For Finance Department
- ✅ Reconcile receipts with bank deposits daily
- ✅ Print receipts for customer records
- ✅ Monitor outstanding balances regularly
- ✅ Review journal entries for accuracy
- ✅ Use date filters to generate period reports

### For Management
- ✅ Monitor "Outstanding" amount on dashboard
- ✅ Review aging of accounts receivable
- ✅ Check payment status before approving new orders for credit customers
- ✅ Use reports to track cash flow

## Troubleshooting

### Cannot Record Payment
- **Check**: Is order status "Delivered"?
- **Check**: Is payment status already "Paid"?
- **Check**: Are you logged in as Sales department (not GM)?

### Receipt Not Appearing in Finance
- **Check**: Was payment successfully recorded? (check for success message)
- **Check**: Refresh Finance page
- **Check**: Verify date range filter in Finance includes payment date

### Payment Amount Rejected
- **Check**: Is amount greater than zero?
- **Check**: Is amount less than or equal to outstanding balance?
- **Check**: Did you enter valid decimal number?

## Report Generation

### Sales Revenue Report
1. Go to Finance → Overview
2. Set date range
3. View "Sales Revenue" summary
4. Compare with "Cash Received" to see collection rate

### Outstanding Balances Report
1. Go to Sales department
2. View "Outstanding" card on dashboard
3. Filter by status to see unpaid/partial orders
4. Click orders to see payment details

### Receipt History
1. Go to Finance → Cash Receipts
2. Set date range
3. Filter by reference_type = "sale"
4. Export or print as needed

## Contact & Support

For questions about:
- **Creating orders**: Contact Sales Department Manager
- **Recording payments**: Contact Sales Department Manager
- **Financial reports**: Contact Finance Department Manager
- **System issues**: Contact IT Administrator
