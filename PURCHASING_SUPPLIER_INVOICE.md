# Purchasing - Supplier Invoice Number Feature

## Overview
Purchase orders now support supplier invoice numbers that can be entered during PO creation and are displayed on printable invoices.

## Features Implemented

### 1. Supplier Invoice Number Field
- Added optional "Supplier Invoice Number" field in new PO modal
- Field accepts any text format (e.g., SI-2026-001, INV-12345, etc.)
- User can provide supplier's invoice number at PO creation time
- Field is optional - can be left blank if invoice number not yet available

### 2. Automatic Journal Entry Creation
- **NEW**: When supplier invoice number is provided during PO creation:
  - System automatically creates journal entry for Accounts Payable
  - Debit: Inventory (or Purchases)
  - Credit: Accounts Payable
  - Amount: Total PO amount
  - Reference: Supplier invoice number
  - Description includes PO number, supplier invoice, and supplier name
- Payment status automatically set to "unpaid"
- Invoice date set to current date
- Creates proper accounting trail from the start

### 3. Database Storage
- Supplier invoice number saved to `purchase_orders.supplier_invoice_number` column
- Invoice date saved to `purchase_orders.supplier_invoice_date`
- Payment status set to "unpaid" when invoice provided
- Journal entry linked to PO via `source_type='purchase_order'` and `source_id=po_id`
- Column already exists from previous migration (`database/purchasing_invoice_feature.sql`)
- Supports up to 100 characters

### 4. Display & Printing
- Supplier invoice number displayed prominently in PO detail page (if provided)
- Print button available next to invoice number for quick access
- Invoice print template shows supplier invoice number at top of document
- Print template includes:
  - Supplier invoice number (if provided)
  - Our internal PO number
  - Order date and delivery date
  - Supplier information
  - All line items with quantities and prices
  - Total amount and payment status

## Files Modified

### Controllers
- `app/controllers/PurchasingController.php`
  - Updated `createPO()` method to accept and save `supplier_invoice_number`
  - Added to approval request title for better tracking

### Views
- `app/views/purchasing/index.php`
  - Added "Supplier Invoice Number" field to new PO modal
  - Updated JavaScript `submitPO()` function to include invoice number in form data
  - Reorganized modal layout for better field grouping

- `app/views/purchasing/po_detail.php`
  - Added alert box showing supplier invoice number (when provided)
  - Added print button next to invoice number for quick access

### Existing (Already Complete)
- `app/views/purchasing/invoice_print.php` - Print template already supports supplier invoice number
- `database/purchasing_invoice_feature.sql` - Database column already exists

## User Flow

### Creating PO with Supplier Invoice & Journal Entry
1. User clicks "New PO" button
2. Fills in supplier information
3. Enters "Supplier Invoice Number" (e.g., SI-2026-001)
4. Adds line items with quantities and prices
5. Submits PO for approval
6. **System automatically**:
   - Saves invoice number with PO
   - Sets payment status to "unpaid"
   - Records invoice date as today
   - **Creates journal entry**:
     - Debit: Inventory ₱X,XXX.XX
     - Credit: Accounts Payable ₱X,XXX.XX
     - Reference: Supplier invoice number
   - Links journal entry to PO
7. Success message confirms: "PO created. Supplier Invoice recorded. Journal entry created for Accounts Payable."

### Creating PO without Supplier Invoice
1. User creates PO without entering invoice number
2. No journal entry created yet
3. Payment status remains null
4. Can add invoice later using "Record Supplier Invoice" feature

### Viewing & Printing
1. User opens PO detail page
2. If supplier invoice number was provided:
   - Blue alert box shows invoice number at top
   - Print button available for quick access
3. User clicks print button
4. Invoice opens in new tab with:
   - Supplier invoice number prominently displayed
   - Our internal PO number
   - All order details and line items
   - Professional print-ready format

### Adding Invoice Number Later
- If invoice number wasn't available at PO creation:
  - Can be added later using "Record Supplier Invoice" feature
  - Available when PO status = "delivered"
  - Includes invoice date, payment terms, and due date

## Database Schema

### purchase_orders table
- `supplier_invoice_number` VARCHAR(100) - Supplier's invoice number (optional)
- `supplier_invoice_date` DATE - Date of supplier's invoice (optional)
- `payment_terms` VARCHAR(50) - Payment terms (e.g., Net 30)
- `payment_due_date` DATE - Payment due date
- `payment_status` ENUM - 'unpaid', 'partial', 'paid'
- `amount_paid` DECIMAL(15,2) - Amount paid so far

## Use Cases

### Scenario 1: Invoice Number Known at PO Creation (with Journal Entry)
- Supplier provides invoice number upfront
- User enters it when creating PO
- **System automatically creates Accounts Payable journal entry**
- Invoice number tracked from the start
- Appears on all documents and reports
- Accounting records immediately reflect the liability
- Finance team can see outstanding payables right away

### Scenario 2: Invoice Number Added Later
- PO created without invoice number
- No journal entry created initially
- Supplier sends invoice after delivery
- User records invoice using "Record Supplier Invoice" feature
- Invoice number added along with payment terms
- Journal entry created at that time

### Scenario 3: No Supplier Invoice
- Some suppliers don't provide formal invoices
- Field can be left blank
- System uses internal PO number for tracking
- Journal entry created when "Record Supplier Invoice" is used
- Still creates proper accounts payable entries

## Benefits

1. **Automatic Accounting** - Journal entries created automatically when invoice provided
2. **Better Tracking** - Link supplier invoices to internal POs
3. **Immediate Liability Recording** - Accounts Payable recorded as soon as invoice received
4. **Audit Trail** - Clear reference to supplier's documentation in journal entries
5. **Payment Matching** - Easy to match payments to supplier invoices
6. **Professional Documents** - Print invoices showing both numbers
7. **Flexibility** - Optional field doesn't block PO creation
8. **Accurate Financial Reports** - Outstanding payables immediately visible in reports

## Testing Checklist

- [x] Create PO with supplier invoice number
- [x] Verify journal entry created automatically (Debit: Inventory, Credit: Accounts Payable)
- [x] Verify payment status set to "unpaid"
- [x] Verify invoice date set to current date
- [x] Create PO without supplier invoice number
- [x] Verify no journal entry created when invoice not provided
- [x] View PO detail with invoice number
- [x] View PO detail without invoice number
- [x] Print invoice with supplier invoice number
- [x] Print invoice without supplier invoice number
- [x] Verify invoice number appears in approval request title
- [x] Verify invoice number saved to database correctly
- [x] Verify journal entry linked to PO correctly
- [x] Check Finance reports show Accounts Payable correctly

## Notes

- Supplier invoice number is completely optional
- **When provided, automatically creates Accounts Payable journal entry**
- Can be any format - no validation applied
- Recommended format: SI-YYYYMMDD-XXXX or supplier's own format
- Invoice print template automatically handles missing invoice numbers
- Field is separate from "Record Supplier Invoice" feature (which adds payment tracking)
- Both features work together for complete invoice management
- Journal entry uses supplier invoice number as reference for easy lookup
- Debit account is "Inventory" (can be changed to "Purchases" based on accounting method)
- Credit account is "Accounts Payable" to track liability
- Journal entry linked to PO via `source_type='purchase_order'` and `source_id`

## Accounting Flow

### With Supplier Invoice at PO Creation:
1. **PO Created** → Journal Entry: Debit Inventory, Credit Accounts Payable
2. **Payment Made** → Journal Entry: Debit Accounts Payable, Credit Cash/Bank
3. **Result**: Inventory increased, Cash decreased, Accounts Payable cleared

### Without Supplier Invoice at PO Creation:
1. **PO Created** → No journal entry yet
2. **Invoice Recorded Later** → Journal Entry: Debit Inventory, Credit Accounts Payable
3. **Payment Made** → Journal Entry: Debit Accounts Payable, Credit Cash/Bank
4. **Result**: Same final outcome, just delayed recording

## Status
✅ **COMPLETE** - Supplier invoice number field added with automatic journal entry creation for Accounts Payable
