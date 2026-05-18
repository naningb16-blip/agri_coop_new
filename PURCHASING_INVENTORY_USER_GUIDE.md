# Purchasing to Inventory - Quick User Guide

## ✅ Good News: It's Already Working!

The purchasing department is **already integrated** with inventory. When you receive goods and mark a purchase order as "delivered", the stock is **automatically added** to inventory.

## How to Use It

### Step 1: Create Purchase Order

1. Go to **Purchasing** department
2. Click **"New PO"** button
3. Fill in details:
   - Select supplier
   - Add items (make sure item names match products in inventory)
   - Enter quantities and prices
4. Click **"Submit"**

### Step 2: Wait for Approval

- PO goes to manager/GM for approval
- You'll see status change from "Pending" to "Approved"

### Step 3: Receive Goods

When the supplier delivers:

1. Open the approved PO
2. Verify all items received
3. Click **"Mark as Delivered"** button
4. (Optional) Select which warehouse to store items
5. Click **"Confirm"**

### Step 4: Automatic Inventory Update

**The system automatically:**
- ✅ Adds stock to inventory
- ✅ Creates stock movement record
- ✅ Links to your PO for tracking
- ✅ Updates inventory quantities

**You don't need to do anything else!**

## Important: Product Names Must Match

### ✅ Correct Example

**In Purchase Order:**
- Item Name: "Yellow Corn Seeds"

**In Inventory Products:**
- Product Name: "Yellow Corn Seeds"

**Result:** ✅ Stock added automatically

### ❌ Wrong Example

**In Purchase Order:**
- Item Name: "Yellow Corn Seed" (missing 's')

**In Inventory Products:**
- Product Name: "Yellow Corn Seeds"

**Result:** ❌ Stock NOT added (name doesn't match)

## Tips for Success

### 1. Check Product Names Before Creating PO

Before creating a PO:
1. Go to Inventory department
2. Check the exact product names
3. Use the same names in your PO

### 2. Create Products First

If ordering a new product:
1. Add it to Inventory → Products first
2. Then create the PO using that exact name

### 3. Select the Right Warehouse

When marking as delivered:
- Choose the warehouse where goods are stored
- Don't leave it blank (system will pick first warehouse)

## Checking If It Worked

### Method 1: Check Inventory

1. Go to **Inventory** department
2. Look for the product
3. Check if quantity increased

### Method 2: Check Stock Movements

1. Go to **Inventory** → Stock Movements
2. Look for entries with "Received from PO #[number]"
3. Verify quantities match your PO

### Method 3: Check PO Details

1. Open your PO
2. Look for stock movement information
3. Should show items added to inventory

## What If Stock Wasn't Added?

### Check 1: Product Name Match

- Compare PO item name with inventory product name
- Must match exactly (including spaces, capitals)

### Check 2: Product Exists

- Go to Inventory → Products
- Search for the product
- If not found, add it first

### Check 3: PO Status

- PO must be marked as "Delivered"
- Check PO status in purchasing department

### Check 4: Warehouse

- At least one warehouse must exist
- Check Inventory → Warehouses

## Common Scenarios

### Scenario 1: Regular Purchase

```
1. Create PO for "White Corn - 50 bags"
2. PO approved by manager
3. Supplier delivers goods
4. Mark PO as "Delivered"
5. Select "Main Warehouse"
6. ✅ 50 bags added to inventory automatically
```

### Scenario 2: Multiple Items

```
1. Create PO with:
   - Yellow Corn Seeds - 100 kg
   - Fertilizer - 50 bags
   - Pesticide - 20 liters
2. PO approved
3. All items delivered
4. Mark as "Delivered"
5. ✅ All 3 items added to inventory
```

### Scenario 3: New Product

```
1. Need to order new product "Hybrid Seeds Type A"
2. First: Add "Hybrid Seeds Type A" to Inventory → Products
3. Then: Create PO with "Hybrid Seeds Type A"
4. After delivery: Mark as delivered
5. ✅ Stock added to inventory
```

## Quick Reference

| Action | Department | Result |
|--------|-----------|--------|
| Create PO | Purchasing | PO created, pending approval |
| Approve PO | Manager/GM | PO approved, ready to order |
| Mark Delivered | Purchasing | ✅ Stock automatically added to inventory |
| View Stock | Inventory | See updated quantities |
| Check Movements | Inventory | See PO stock-in records |

## Need Help?

### If stock is not being added:

1. **Check product names** - Must match exactly
2. **Verify product exists** - Add to inventory first if needed
3. **Confirm PO status** - Must be "Delivered"
4. **Run diagnostic** - Ask admin to run `diagnostic.php`

### Contact:

- **Purchasing Issues**: Purchasing Department Manager
- **Inventory Issues**: Inventory Department Manager
- **System Issues**: IT Administrator

## Summary

✅ **Purchasing automatically updates inventory when PO is delivered**

**Remember:**
- Product names must match exactly
- Mark PO as "Delivered" to trigger stock-in
- Select correct warehouse
- Check inventory to verify

**That's it! The system handles the rest automatically.**
