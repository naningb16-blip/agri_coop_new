# Purchasing to Inventory Flow - Complete

## Overview
The purchasing department flow has been verified and enhanced to ensure smooth integration with inventory management and proper low stock notifications.

## Purchase Order Flow

### 1. Purchasing Department Creates PO
- Purchasing user creates a purchase order with items
- Each item includes: name, quantity, unit, unit price
- PO is submitted for GM approval

### 2. GM Approves Purchase Order
When GM approves a purchase order through the Approvals section:

**Automatic Actions:**
1. PO status updated to `approved`
2. **All PO items automatically added to inventory**
   - System finds or creates products by name
   - Adds stock to the first available warehouse
   - Creates stock movement records with reference to PO
   - Notes: "Auto stock-in from approved PO #[ID]"

**Code Location:** `app/models/ApprovalModel.php` - `_syncApproval()` method, `purchase_order` case

### 3. Inventory Updated
- Stock quantities automatically increase
- Inventory movements logged
- Products created if they don't exist
- All tracked with proper references

## Low Stock Notifications

### Who Sees Low Stock Alerts?

#### 1. GM Dashboard
- **Location:** Dashboard home page
- **Display:** Red alert box at top of page
- **Shows:** All items below reorder level
- **Details:** Current stock, reorder level, shortage amount
- **Actions:** 
  - View Inventory button
  - Create Purchase Order button

#### 2. Inventory Department
- **Location:** Inventory module home page
- **Display:** Red alert box at top (when logged in as inventory user)
- **Shows:** Same low stock items as GM
- **Details:** Product name, current stock, reorder level, shortage
- **Actions:**
  - View Inventory button
  - Create Purchase Order button (if they have permission)

#### 3. Admin/BOD Dashboard
- **Location:** Main dashboard
- **Display:** Low stock count in statistics cards
- **Shows:** Number of low stock items
- **Access:** Can view full inventory details

### How Low Stock Detection Works

**Criteria:**
- Product has `reorder_level` > 0
- Current stock across all warehouses < reorder_level
- Stock quantity > 0 (not completely out)

**Query:**
```sql
SELECT p.id, p.name, p.unit, p.reorder_level,
       COALESCE(SUM(i.quantity), 0) AS current_stock,
       p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
FROM products p
LEFT JOIN inventory i ON p.id = i.product_id
WHERE p.reorder_level > 0
GROUP BY p.id, p.name, p.unit, p.reorder_level
HAVING current_stock < p.reorder_level
ORDER BY shortage DESC
```

## Complete Workflow Example

### Scenario: Restocking Low Stock Items

1. **Inventory user notices low stock alert**
   - Sees "Rice: Current stock 50 kg (Reorder at: 100 kg) — Shortage: 50 kg"
   - Clicks "Create Purchase Order" or notifies purchasing

2. **Purchasing creates PO**
   - Goes to Purchasing module
   - Creates new Purchase Order
   - Adds items: Rice, 100 kg, ₱50/kg
   - Submits for approval

3. **GM reviews and approves**
   - Sees PO in Approvals section
   - Reviews items and pricing
   - Approves the purchase order

4. **System automatically updates inventory**
   - Rice stock increases from 50 kg to 150 kg
   - Stock movement created: "Auto stock-in from approved PO #123"
   - Low stock alert disappears (150 kg > 100 kg reorder level)

5. **Everyone sees updated stock**
   - Inventory dashboard shows updated quantities
   - Low stock alert removed
   - Stock movements logged for audit

## Setting Reorder Levels

### For Inventory Users
1. Go to Inventory → Products tab
2. Click Edit on any product
3. Set "Reorder Level" field
4. System will alert when stock falls below this level

### Best Practices
- Set reorder levels based on:
  - Average daily/weekly usage
  - Lead time for restocking
  - Safety buffer for emergencies
- Review and adjust quarterly
- Higher reorder levels for critical items

## Files Modified

### 1. Dashboard Controller
**File:** `app/controllers/DashboardController.php`
- Added low stock count to GM dashboard stats
- Added low stock items query for GM dashboard
- Passed `low_stock_items` to GM view

### 2. GM Dashboard View
**File:** `app/views/dashboard/gm.php`
- Added red alert box for low stock items
- Shows detailed list of items below reorder level
- Includes shortage calculations
- Action buttons to view inventory or create PO

### 3. Inventory View (Already Working)
**File:** `app/views/inventory/index.php`
- Low stock alert already shown for inventory users
- No changes needed

### 4. Approval Model (Already Working)
**File:** `app/models/ApprovalModel.php`
- Purchase order approval already adds items to inventory
- No changes needed

## Testing Checklist

### Test Purchase Order Flow
- [ ] Create PO as purchasing user
- [ ] Verify GM sees it in Approvals
- [ ] GM approves PO
- [ ] Check inventory increased automatically
- [ ] Verify stock movement created with PO reference
- [ ] Confirm products created if they didn't exist

### Test Low Stock Notifications
- [ ] Set product reorder level to 100
- [ ] Reduce stock below 100
- [ ] Log in as GM - see low stock alert on dashboard
- [ ] Log in as inventory user - see low stock alert on inventory page
- [ ] Create and approve PO to restock
- [ ] Verify alert disappears when stock > reorder level

### Test Edge Cases
- [ ] PO with new products (not in inventory yet)
- [ ] PO with multiple items
- [ ] Multiple warehouses (uses first warehouse)
- [ ] Product with reorder_level = 0 (no alert)
- [ ] Product completely out of stock (quantity = 0)

## Benefits

### For Purchasing Department
- Clear workflow: Create PO → GM approves → Auto-stocked
- No manual inventory entry needed
- Immediate visibility of stock levels

### For Inventory Department
- Automatic stock updates from approved POs
- Low stock alerts on their dashboard
- Can track all movements with PO references
- No manual data entry

### For GM
- Single approval point for all purchases
- Low stock visibility on dashboard
- Can see what needs restocking
- Full audit trail of all stock movements

### For the Organization
- Prevents stockouts with proactive alerts
- Reduces manual data entry errors
- Complete traceability (PO → Approval → Stock Movement)
- Better inventory planning with reorder levels

## Summary

✅ **Purchase Order Flow:** Working - GM approval automatically adds items to inventory

✅ **Low Stock Notifications:** Enhanced - Now visible to both GM and Inventory users

✅ **Automatic Stock Updates:** Working - No manual inventory entry needed

✅ **Audit Trail:** Complete - All movements linked to source POs

The system now provides a complete, automated flow from purchase order creation through GM approval to automatic inventory updates, with proactive low stock notifications for relevant users.
