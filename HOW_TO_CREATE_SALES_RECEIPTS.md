# How to Create Receipts in Sales Department

## 📋 Overview

Receipts in the Sales department are **automatically generated** when you create a sales order with payment. The receipt then appears in the Finance department for tracking.

---

## 🎯 Two Ways to Create Receipts

### Method 1: Create Sales Order with Payment (Recommended)
### Method 2: Record Payment for Existing Sales Order

---

## Method 1: Create Sales Order with Payment ⭐

This is the most common way - create a sales order and record payment at the same time.

### Step-by-Step Guide:

#### 1. **Login as Sales User**
- Username: Your sales account
- Navigate to: **Sales** menu

#### 2. **Click "New Sales Order"**
- Look for the green button with "+ New Order"

#### 3. **Fill in Customer Information**
```
Customer: Select existing or type new customer name
Order Date: Today's date (auto-filled)
Delivery Date: Expected delivery date
```

#### 4. **Add Products**
```
Product: Select from dropdown
Quantity: Enter amount (e.g., 100)
Unit: kg/bags/pcs (auto-filled from product)
Unit Price: Enter price per unit (e.g., 50.00)
```

Click **"Add Item"** to add more products

#### 5. **Payment Section** (Important!)

This is where the receipt is created:

```
Payment Type:
○ Cash Receipt (immediate payment)
○ Charge Invoice (pay later)
○ Credit (installment)

Payment Status:
○ Unpaid (no payment yet)
○ Partial (some payment received)
○ Paid (full payment received)

Amount Paid: Enter amount received (e.g., 5000.00)
```

**Example**:
- Total Amount: ₱5,000.00
- Payment Type: **Cash Receipt**
- Payment Status: **Paid**
- Amount Paid: **₱5,000.00**

#### 6. **Add Notes** (Optional)
```
Notes: Any additional information
```

#### 7. **Click "Create Sales Order"**

---

## What Happens Next? 🔄

### Automatic Process:

1. ✅ **Sales Order Created**
   - Status: Pending
   - Goes to approval workflow

2. ✅ **Receipt Automatically Generated**
   - Receipt Number: REC-YYYYMMDD-XXXX
   - Amount: Same as "Amount Paid"
   - Type: Cash Receipt or Charge Invoice
   - Customer: From sales order

3. ✅ **Receipt Appears in Finance**
   - Finance → Cash Receipts tab
   - Shows receipt number, customer, amount
   - Linked to sales order

4. ✅ **Journal Entry Created**
   - Debit: Cash/Bank
   - Credit: Accounts Receivable
   - Automatic accounting entry

---

## Method 2: Record Payment for Existing Order

If you created a sales order without payment, you can add payment later.

### Step-by-Step:

#### 1. **Go to Sales Order Detail**
- Click on the sales order number
- Or click "View" button

#### 2. **Look for Payment Section**
- Shows current payment status
- "Record Payment" button

#### 3. **Click "Record Payment"**

#### 4. **Fill in Payment Details**
```
Payment Type: Cash Receipt / Charge Invoice
Amount: Enter amount received
Payment Method: Cash / Bank Transfer / Check
Payment Date: Date of payment
Notes: Optional
```

#### 5. **Click "Save Payment"**

#### 6. **Receipt Generated**
- Automatically creates receipt
- Updates payment status
- Appears in Finance

---

## Receipt Types Explained 📝

### 1. Cash Receipt
- **When**: Customer pays immediately
- **Badge**: Green "Cash Receipt"
- **Finance Impact**: Increases cash balance
- **Example**: Customer pays ₱5,000 cash on delivery

### 2. Charge Invoice
- **When**: Customer will pay later (credit)
- **Badge**: Yellow "Charge Invoice"
- **Finance Impact**: Increases accounts receivable
- **Example**: Customer orders ₱10,000, pays next month

---

## Payment Status Explained 💰

### Unpaid
- No payment received yet
- Amount Paid: ₱0
- Outstanding: Full amount

### Partial
- Some payment received
- Amount Paid: Less than total
- Outstanding: Remaining balance
- **Example**: Total ₱10,000, Paid ₱5,000, Outstanding ₱5,000

### Paid
- Full payment received
- Amount Paid: Equal to total
- Outstanding: ₱0

---

## Complete Example Walkthrough 🎓

### Scenario: Selling 100 bags of rice

#### Step 1: Create Sales Order
```
Customer: Juan Dela Cruz
Order Date: May 4, 2026
Delivery Date: May 10, 2026

Products:
- Rice (Premium) | 100 bags | ₱50/bag = ₱5,000

Total Amount: ₱5,000.00
```

#### Step 2: Record Payment
```
Payment Type: Cash Receipt
Payment Status: Paid
Amount Paid: ₱5,000.00
Payment Method: Cash
```

#### Step 3: Submit
- Click "Create Sales Order"
- Order goes to approval

#### Step 4: Automatic Receipt
```
Receipt Number: REC-20260504-A1B2
Customer: Juan Dela Cruz
Amount: ₱5,000.00
Type: Cash Receipt
Date: May 4, 2026
```

#### Step 5: Check Finance
- Go to Finance → Cash Receipts
- See receipt: REC-20260504-A1B2
- Amount: ₱5,000.00
- Customer: Juan Dela Cruz

---

## Where to Find Receipts 🔍

### In Sales Department:
1. Go to **Sales** menu
2. Click on sales order
3. View payment details
4. See receipt number and status

### In Finance Department:
1. Go to **Finance** menu
2. Click **Cash Receipts** tab
3. See all receipts from sales
4. Filter by date range
5. Print receipt if needed

---

## Printing Receipts 🖨️

### From Sales:
1. Open sales order detail
2. Click "Print Receipt" button
3. Receipt opens in new window
4. Print or save as PDF

### From Finance:
1. Go to Finance → Cash Receipts
2. Find the receipt
3. Click printer icon
4. Receipt opens in new window
5. Print or save as PDF

---

## Receipt Information Included 📄

Every receipt shows:
- ✅ Receipt Number (REC-YYYYMMDD-XXXX)
- ✅ Date
- ✅ Customer Name
- ✅ Items Purchased
- ✅ Quantity and Unit
- ✅ Amount
- ✅ Payment Method
- ✅ Received By (your name)
- ✅ Company Information

---

## Common Scenarios 💡

### Scenario 1: Full Payment on Delivery
```
Total: ₱10,000
Payment Type: Cash Receipt
Payment Status: Paid
Amount Paid: ₱10,000
```
**Result**: Receipt for ₱10,000 created immediately

### Scenario 2: Partial Payment
```
Total: ₱10,000
Payment Type: Cash Receipt
Payment Status: Partial
Amount Paid: ₱5,000
```
**Result**: Receipt for ₱5,000 created, ₱5,000 outstanding

### Scenario 3: Credit Sale (Pay Later)
```
Total: ₱10,000
Payment Type: Charge Invoice
Payment Status: Unpaid
Amount Paid: ₱0
```
**Result**: Charge invoice created, no cash receipt yet

### Scenario 4: Payment After Delivery
```
1. Create order with Payment Status: Unpaid
2. Later, record payment
3. Receipt generated when payment recorded
```

---

## Tips & Best Practices ✨

### ✅ DO:
- Always record payment when received
- Use "Cash Receipt" for immediate payments
- Use "Charge Invoice" for credit sales
- Print receipt for customer
- Keep payment method accurate
- Add notes for reference

### ❌ DON'T:
- Don't create sales order without customer
- Don't forget to record payment
- Don't use wrong payment type
- Don't skip approval process

---

## Troubleshooting 🔧

### Problem: Receipt not appearing in Finance
**Solution**: 
- Check if sales order was created successfully
- Verify payment amount > 0
- Check if payment status is not "Unpaid"
- Refresh Finance page

### Problem: Can't create sales order
**Solution**:
- Check if you have sales permission
- Verify customer is selected
- Ensure at least one product added
- Check all required fields filled

### Problem: Wrong receipt amount
**Solution**:
- Edit sales order
- Update payment amount
- Receipt will be updated automatically

---

## Quick Reference Card 📋

### Creating Receipt via Sales Order:

1. **Sales** → **New Order**
2. Select **Customer**
3. Add **Products**
4. Set **Payment Type**: Cash Receipt
5. Set **Payment Status**: Paid
6. Enter **Amount Paid**
7. Click **Create**
8. ✅ Receipt auto-generated!

### Viewing Receipt:

**In Sales**:
- Sales → Order Detail → Payment Section

**In Finance**:
- Finance → Cash Receipts → Find receipt

---

## Summary

### Key Points:
- ✅ Receipts are **automatically created** with sales orders
- ✅ Choose **Cash Receipt** for immediate payment
- ✅ Choose **Charge Invoice** for credit sales
- ✅ Receipts appear in **Finance → Cash Receipts**
- ✅ Can **print** receipts for customers
- ✅ **Journal entries** created automatically

### The Process:
```
Sales Order → Payment Info → Create → Receipt Generated → Appears in Finance
```

---

## Need Help?

- Check `SALES_PAYMENT_USER_GUIDE.md` for detailed payment guide
- Check `SALES_PAYMENT_INTEGRATION.md` for technical details
- Contact admin if issues persist

---

**Last Updated**: May 4, 2026  
**Status**: Active  
**Department**: Sales & Finance
