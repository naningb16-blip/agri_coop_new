# Quick Testing Guide - All Features

## 🚀 Ready to Test!

All features are implemented. Follow this guide to test everything.

---

## Test 1: Finance - Billing Expenses (5 min)

### Steps:
1. Login as Finance user
2. Go to **Finance** → **Expenses** tab
3. Click **"New Expense"**
4. Fill in:
   - Category: **Electric Bill**
   - Amount: **15000**
   - Expense Date: Today
   - Billing Month: **2026-05**
   - Due Date: **2026-06-10**
   - Vendor: **Manila Electric Company**
   - Account: **1234-5678-9012**
   - Description: **May 2026 electricity**
5. Click **Submit**

### Expected Results:
✅ Expense appears in table  
✅ Shows "Electric" badge  
✅ Shows "May 2026" billing month  
✅ Shows due date "Jun 10"  
✅ Shows vendor name  
✅ Status: Pending  
✅ Goes to approval workflow  

---

## Test 2: Inventory - Low Stock Alert (5 min)

### Steps:
1. Login as Admin or Inventory user
2. Go to **Inventory** → **Products** tab
3. Edit any product (or create new):
   - Name: **Test Rice**
   - Reorder Level: **50**
   - Save
4. Go to **Stock** tab
5. If stock > 50, reduce it:
   - Click **"Request Release"**
   - Select product, quantity to bring below 50
   - Submit and approve
6. Refresh page

### Expected Results:
✅ Yellow alert banner appears at top  
✅ Shows "Low Stock Alert" heading  
✅ Lists "Test Rice" with current stock  
✅ Shows shortage amount  
✅ "Low Stock" badge in stock table  
✅ Red highlighting for quantity  
✅ Summary card shows low stock count  

---

## Test 3: Logistics - Inbound Delivery (10 min)

### Steps:
1. **Create PO** (if needed):
   - Go to Purchasing
   - Create PO for 100kg Sugar
   - Submit and approve

2. **Create Inbound Delivery**:
   - Go to **Logistics**
   - Click **"New Delivery"**
   - Reference Type: **Purchase Order (Inbound to Warehouse)**
   - Select your PO
   - **Warehouse selector appears** ✅
   - Select: **Main Warehouse**
   - Origin: **Supplier Address**
   - Destination: **Main Warehouse**
   - Driver: **Juan Dela Cruz**
   - Vehicle: **ABC 1234**
   - Add items (should match PO)
   - Click **Create Delivery**

3. **Process Delivery**:
   - Find your delivery in table
   - Badge shows: **Inbound** (green) ✅
   - Label shows: **To Warehouse** ✅
   - Click **Dispatch** (truck icon)
   - Status: In Transit
   - Click **Mark Delivered** (check icon)
   - Status: Delivered

4. **Verify Stock**:
   - Go to **Inventory** → **Stock**
   - Find Sugar in Main Warehouse
   - Stock increased by 100kg ✅

### Expected Results:
✅ Warehouse selector appears for inbound  
✅ Delivery created successfully  
✅ Green "Inbound" badge shows  
✅ "To Warehouse" label shows  
✅ Stock added to selected warehouse  
✅ PO status updated to "Delivered"  

---

## Test 4: Logistics - Outbound Delivery (10 min)

### Steps:
1. **Create SO** (if needed):
   - Go to Sales
   - Create SO for 50kg Rice
   - Submit and approve

2. **Create Outbound Delivery**:
   - Go to **Logistics**
   - Click **"New Delivery"**
   - Reference Type: **Sales Order (Outbound to Customer)**
   - Select your SO
   - **No warehouse selector** ✅
   - Origin: **Main Warehouse**
   - Destination: **Customer Address**
   - Driver: **Pedro Santos**
   - Vehicle: **XYZ 5678**
   - Add items (should match SO)
   - Click **Create Delivery**

3. **Process Delivery**:
   - Find your delivery in table
   - Badge shows: **Outbound** (blue) ✅
   - Label shows: **To Customer** ✅
   - Click **Dispatch** (truck icon)
   - Status: In Transit
   - **Check stock now** - should be reduced ✅
   - Click **Mark Delivered** (check icon)
   - Status: Delivered

4. **Verify Stock**:
   - Go to **Inventory** → **Stock**
   - Find Rice
   - Stock decreased by 50kg ✅

### Expected Results:
✅ No warehouse selector for outbound  
✅ Delivery created successfully  
✅ Blue "Outbound" badge shows  
✅ "To Customer" label shows  
✅ Stock deducted when "In Transit"  
✅ SO status updated to "Delivered"  

---

## Test 5: Finance - Sales Receipts (2 min)

### Steps:
1. Go to **Sales**
2. Create sales order with payment
3. Go to **Finance** → **Cash Receipts** tab

### Expected Results:
✅ Receipt appears automatically  
✅ Shows receipt number  
✅ Shows payer name  
✅ Shows amount  
✅ Shows payment method  

---

## Test 6: Finance - Purchase Orders (2 min)

### Steps:
1. Go to **Purchasing**
2. Create and approve a PO
3. Go to **Finance** → **Purchases** tab

### Expected Results:
✅ PO appears automatically  
✅ Shows PO number  
✅ Shows supplier  
✅ Shows amount  
✅ Shows status  
✅ Can click to view details  

---

## Quick Verification Checklist

### Finance ✅
- [ ] Expense categories dropdown works
- [ ] Billing month picker works
- [ ] Due date picker works
- [ ] Vendor name saves
- [ ] Account number saves
- [ ] Expense appears in table with all fields
- [ ] Category badge shows correctly
- [ ] Sales receipts appear automatically
- [ ] Purchase orders appear automatically

### Inventory ✅
- [ ] Reorder level field in product form
- [ ] Low stock alert banner appears
- [ ] Alert shows correct products
- [ ] "Low Stock" badge in stock table
- [ ] Red highlighting for low stock
- [ ] Summary card shows count
- [ ] Alert only shows for inventory users

### Logistics ✅
- [ ] Inbound badge is green with down arrow
- [ ] Outbound badge is blue with up arrow
- [ ] Warehouse selector appears for inbound only
- [ ] Warehouse selector hidden for outbound
- [ ] Inbound delivery adds stock to warehouse
- [ ] Outbound delivery deducts stock
- [ ] Stock movements are idempotent (no duplicates)
- [ ] PO/SO status updates correctly

---

## Common Issues

### Issue: Categories not showing
**Fix**: Run `database/finance_enhancements.sql`

### Issue: Low stock alert not showing
**Fix**: Set reorder_level > 0 for products

### Issue: Warehouse selector not appearing
**Fix**: Select "Purchase Order (Inbound)" first

### Issue: Stock not updating
**Fix**: Change delivery status to "Delivered" (inbound) or "In Transit" (outbound)

---

## Success Criteria

All tests pass if:
- ✅ No errors in browser console
- ✅ No PHP errors in logs
- ✅ All data saves correctly
- ✅ All badges and labels show correctly
- ✅ Stock movements work correctly
- ✅ Approval workflows trigger
- ✅ Automatic integrations work

---

## After Testing

1. ✅ All tests pass
2. Train users on new features
3. Deploy to production
4. Monitor for issues
5. Collect feedback

---

**Estimated Total Testing Time**: 30-40 minutes

**Status**: Ready to test!

**Next**: Start with Test 1 (Finance) and work through each test.
