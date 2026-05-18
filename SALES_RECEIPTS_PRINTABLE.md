# Sales Receipts - Printable Feature Complete

## Overview
All sales receipts are now printable directly from the sales order detail page.

## Features Implemented

### 1. Cash Sale Invoice Number
- Added optional "Cash Sale Invoice Number" field in new sales order modal
- Field only appears when Payment Type = "Cash"
- User can provide custom invoice number or system auto-generates as `CSI-YYYYMMDD-XXXX`
- Invoice number saved to `sales_orders.invoice_number` column
- Invoice number displayed prominently in order detail view
- Print Invoice button available when invoice number exists

### 2. Printable Receipts
- All payment receipts now displayed in sales order detail page
- Each receipt shows:
  - Receipt number
  - Payment date
  - Amount paid
  - Payment method
  - Print button
- Print button opens receipt in new tab using existing print template
- Receipt print template: `app/views/finance/receipt_print.php`
- Total paid amount calculated and displayed at bottom

## Files Modified

### Controllers
- `app/controllers/SalesController.php`
  - Updated `create()` method to handle cash invoice numbers
  - Updated `detail()` method to fetch receipts for the order

### Views
- `app/views/sales/index.php`
  - Added "Cash Sale Invoice Number" field to new order modal
  - Added toggle function to show/hide field based on payment type
  
- `app/views/sales/detail.php`
  - Added receipts section displaying all payment receipts
  - Added print button for each receipt
  - Shows total paid amount

### Existing (Reused)
- `app/views/finance/receipt_print.php` - Receipt print template (already exists)

## User Flow

### Creating Cash Sale with Invoice
1. User clicks "New Sales Order"
2. Selects Payment Type = "Cash"
3. Cash Sale Invoice Number field appears
4. User can:
   - Enter custom invoice number (e.g., "INV-2024-001")
   - Leave blank for auto-generation (e.g., "CSI-20240507-A1B2")
5. Complete order creation
6. Invoice number saved and displayed in order detail

### Recording Payment & Printing Receipt
1. Order status = "Delivered"
2. User records payment in sales detail page
3. System creates receipt with auto-generated receipt number
4. Receipt appears in "Payment Receipts" section
5. User clicks print button (printer icon)
6. Receipt opens in new tab with print-friendly format
7. User can print or save as PDF

### Viewing All Receipts
- Sales order detail page shows all receipts in chronological order
- Each receipt has individual print button
- Total paid amount displayed at bottom
- Receipt details: number, date, amount, payment method

## Database Schema

### sales_orders table
- `invoice_number` VARCHAR(50) - Stores cash sale invoice number
- `invoice_date` DATETIME - Date invoice was generated

### receipts table (existing)
- `receipt_number` VARCHAR(50) - Auto-generated receipt number
- `reference_type` = 'sale' - Links to sales order
- `reference_id` - Sales order ID
- `amount` DECIMAL(15,2) - Payment amount
- `payment_method` - Cash, bank transfer, check, etc.
- `receipt_date` DATE - Payment date
- `payer_name` - Customer name
- `receipt_type` - 'cash_receipt' or 'charge_invoice'
- `item_description` - Description of items sold
- `received_by` - User who recorded payment

## Testing Checklist

- [x] Create cash sale with custom invoice number
- [x] Create cash sale with auto-generated invoice number
- [x] Create credit/charge sale (no invoice number)
- [x] Record payment for delivered order
- [x] View receipts in sales detail page
- [x] Print individual receipt
- [x] Verify receipt shows correct information
- [x] Verify total paid amount calculation
- [x] Test with multiple payments on same order

## Notes

- Invoice numbers are only for cash sales (immediate payment)
- Credit/charge sales don't get invoice numbers until payment
- Receipt print template supports both cash receipts and charge invoices
- Print button opens in new tab for easy printing/saving
- Receipt format includes company name, receipt number, date, payer info, amount, and signatures
- All receipts are stored in `receipts` table with proper foreign key relationships

## Status
✅ **COMPLETE** - All requested features implemented and tested
