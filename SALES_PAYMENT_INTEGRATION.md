# Sales Payment Integration - Implementation Summary

## Overview
Implemented cash receipt and charge invoice functionality in the Sales department that automatically creates financial records visible in the Finance department.

## Changes Made

### 1. Database Migration
**File:** `database/sales_payment_integration.sql`

Added payment tracking fields to `sales_orders` table:
- `payment_type` - ENUM('cash','charge','credit') - Type of payment arrangement
- `payment_status` - ENUM('unpaid','partial','paid') - Current payment status
- `amount_paid` - DECIMAL(12,2) - Total amount paid so far
- `receipt_id` - INT - Link to the receipt record in finance

### 2. Sales Controller Updates
**File:** `app/controllers/SalesController.php`

#### Modified Methods:
- **`create()`** - Now captures payment_type when creating sales orders
- **`index()`** - Updated summary query to include total_paid and total_outstanding amounts

#### New Methods:
- **`recordPayment()`** - Records payment against a sales order
  - Creates receipt in finance module
  - Updates payment status (unpaid → partial → paid)
  - Generates receipt number (REC-YYYYMMDD-XXXX)
  - Creates journal entry for accounting
  - Determines receipt type based on payment_type (cash → cash_receipt, charge/credit → charge_invoice)

- **`_createJournalEntry()`** - Private helper to create journal entries

### 3. Sales Index View Updates
**File:** `app/views/sales/index.php`

- Added 2 new summary cards: "Collected" and "Outstanding"
- Added "Payment" column to orders table showing payment type and status
- Added payment type selector in "New Order" modal (Cash/Charge/Credit)
- Updated JavaScript `submitOrder()` function to include payment_type

### 4. Sales Detail View Updates
**File:** `app/views/sales/detail.php`

- Added payment information display section showing:
  - Payment Type (badge)
  - Payment Status (badge)
  - Amount Paid / Total Amount
  
- Added "Record Payment" card for delivered orders with unpaid/partial status:
  - Amount input (with max validation)
  - Payment method selector
  - Payment date picker
  - Notes field
  - "Record Payment" button

- Added JavaScript `recordPayment()` function to handle payment submission

### 5. Router Updates
**File:** `public/index.php`

Added new route:
```php
$router->post('/sales/record-payment', 'SalesController', 'recordPayment');
```

### 6. Finance Integration
The existing Finance module already displays receipts properly. Sales receipts will automatically appear in:
- Finance → Cash Receipts tab
- Shows receipt type badge (Cash Receipt / Charge Invoice)
- Displays customer name, amount, payment method
- Includes print functionality

## How It Works

### Creating a Sales Order with Payment Type
1. User creates sales order and selects payment type (Cash/Charge/Credit)
2. Order is created with `payment_status='unpaid'` and `amount_paid=0`
3. Order goes through normal approval workflow

### Recording Payment
1. After order is delivered, "Record Payment" section appears
2. User enters payment amount (can be partial or full)
3. System:
   - Creates receipt in `receipts` table
   - Generates unique receipt number
   - Updates `sales_orders` payment fields
   - Creates journal entry for accounting
   - Sets receipt_type based on payment_type:
     - Cash orders → cash_receipt
     - Charge/Credit orders → charge_invoice

### Payment Status Logic
- **Unpaid**: amount_paid = 0
- **Partial**: 0 < amount_paid < total_amount
- **Paid**: amount_paid >= total_amount

### Finance Department View
- All sales receipts appear in Finance → Cash Receipts tab
- Receipts are labeled as "Cash Receipt" or "Charge Invoice"
- Shows customer name, items, amount, payment method
- Can be printed for customer records
- Automatically creates journal entries for accounting

## Database Schema Changes

```sql
ALTER TABLE sales_orders
    ADD COLUMN payment_type ENUM('cash','charge','credit') DEFAULT 'cash',
    ADD COLUMN payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
    ADD COLUMN amount_paid DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN receipt_id INT NULL;
```

## Journal Entry Accounting

When payment is recorded:
- **Debit Account**: Cash or Bank (based on payment method)
- **Credit Account**: 
  - Accounts Receivable (for charge invoices)
  - Sales Revenue (for cash receipts)
- **Reference**: Receipt number (REC-YYYYMMDD-XXXX)
- **Source**: Links to receipt record

## User Roles & Permissions

- **Sales Department**: Can create orders, record payments
- **GM**: View-only access (cannot record payments)
- **Finance Department**: Can view all receipts and journal entries
- **Admin**: Full access to all functions

## Migration Instructions

Run the migration to add payment fields to sales_orders:
```bash
mysql -u [username] -p agri_coop < database/sales_payment_integration.sql
```

Or through the web interface:
```
Navigate to: public/migrate.php
```

## Testing Checklist

- [ ] Create sales order with Cash payment type
- [ ] Create sales order with Charge payment type
- [ ] Create sales order with Credit payment type
- [ ] Approve and deliver an order
- [ ] Record partial payment
- [ ] Verify payment status changes to "partial"
- [ ] Record remaining payment
- [ ] Verify payment status changes to "paid"
- [ ] Check receipt appears in Finance → Cash Receipts
- [ ] Verify receipt type badge (Cash Receipt vs Charge Invoice)
- [ ] Verify journal entry is created
- [ ] Print receipt from Finance module
- [ ] Verify GM cannot record payments (view-only)

## Summary Statistics

The Sales index now shows:
- **Total Orders**: Count of all orders
- **Pending**: Orders awaiting approval
- **Approved**: Orders approved but not delivered
- **Delivered**: Orders delivered to customers
- **Revenue**: Total value of non-cancelled orders
- **Collected**: Total amount actually paid by customers
- **Outstanding**: Total amount still owed (unpaid + partial)

## Notes

- Receipts are automatically generated when payment is recorded
- Receipt numbers follow format: REC-YYYYMMDD-XXXX
- Payment amount is capped at outstanding balance
- Multiple partial payments are supported
- All transactions create audit trail in journal entries
- Existing sales orders are migrated with payment_type='cash' and payment_status='unpaid'
