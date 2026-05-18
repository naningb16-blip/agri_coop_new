# Purchasing Department - Inventory Integration

## Overview
The Purchasing department is **already integrated** with the Inventory department. When purchase orders are received (status changed to "delivered"), the system automatically adds stock to the inventory.

## Current Implementation

### How It Works

1. **Create Purchase Order (PO)**
   - Purchasing department creates a PO with items
   - Items are stored in `purchase_order_items` table
   - PO goes through approval workflow

2. **Approve Purchase Order**
   - Manager/GM approves the PO
   - Status changes from "pending" to "approved"

3. **Mark as Delivered**
   - When goods are received, user marks PO as "delivered"
   - **Automatic inventory update is triggered**

4. **Inventory Stock-In Process**
   - System matches PO items with products by name
   - For each matched product:
     - Creates stock movement record (type='in')
     - Updates inventory quantity
     - Links to PO for traceability

### Code Location

**File:** `app/controllers/PurchasingController.php`

**Method:** `updatePOStatus()`

```php
public function updatePOStatus(): void {
    // ... validation code ...
    
    // Auto stock-in when PO is marked delivered
    if ($status === 'delivered') {
        $items = $this->db->fetchAll(
            "SELECT poi.*, p.id AS product_id FROM purchase_order_items poi
             LEFT JOIN products p ON p.name = poi.item_name
             WHERE poi.po_id = ?", [$id], 'i'
        );
        
        // Use first available warehouse if not specified
        if (!$warehouseId) {
            $wh = $this->db->fetchOne("SELECT id FROM warehouses LIMIT 1");
            $warehouseId = $wh ? (int)$wh['id'] : 0;
        }
        
        if ($warehouseId) {
            require_once __DIR__ . '/../models/InventoryModel.php';
            $inv = new InventoryModel();
            foreach ($items as $item) {
                if (!$item['product_id']) continue;
                $inv->addMovement([
                    'product_id'     => $item['product_id'],
                    'warehouse_id'   => $warehouseId,
                    'type'           => 'in',
                    'quantity'       => $item['quantity'],
                    'reference_type' => 'purchase_order',
                    'reference_id'   => $id,
                    'notes'          => "Received from PO #$id",
                ]);
            }
        }
    }
}
```

## Product Matching Logic

### How Items are Matched to Products

The system matches purchase order items to inventory products **by name**:

```sql
SELECT poi.*, p.id AS product_id 
FROM purchase_order_items poi
LEFT JOIN products p ON p.name = poi.item_name
WHERE poi.po_id = ?
```

### Important Notes

1. **Exact Name Match Required**
   - PO item name must exactly match product name in inventory
   - Case-sensitive matching
   - Example: "Yellow Corn Seeds" must match exactly

2. **Unmatched Items**
   - If PO item name doesn't match any product, it's skipped
   - No error is shown (silent skip)
   - Stock is not added for unmatched items

3. **Warehouse Selection**
   - Can specify warehouse when marking as delivered
   - If not specified, uses first available warehouse
   - All items go to the same warehouse

## Database Tables Involved

### 1. purchase_orders
- Stores PO header information
- Fields: po_number, supplier_id, status, total_amount, etc.

### 2. purchase_order_items
- Stores individual items in each PO
- Fields: item_name, quantity, unit, unit_price, total_price

### 3. products
- Master list of products in the system
- Fields: name, category, unit, description, reorder_level

### 4. inventory
- Current stock levels per product per warehouse
- Fields: product_id, warehouse_id, quantity

### 5. stock_movements
- Audit trail of all stock changes
- Fields: product_id, warehouse_id, type, quantity, reference_type, reference_id

## Workflow Diagram

```
Purchase Order Created
         ↓
    Pending Status
         ↓
   Approval Process
         ↓
   Approved Status
         ↓
  Goods Received
         ↓
Mark as "Delivered" ← User Action
         ↓
System Matches Items to Products (by name)
         ↓
For Each Matched Product:
  - Create Stock Movement (type='in')
  - Update Inventory Quantity
  - Link to PO for Reference
         ↓
Inventory Updated ✓
```

## User Guide

### For Purchasing Department

#### Creating a Purchase Order

1. Navigate to Purchasing → Purchase Orders
2. Click "New PO" button
3. Select or enter supplier name
4. Add items:
   - **Item Name**: Must match product name in inventory exactly
   - Quantity
   - Unit (kg, bags, pcs, etc.)
   - Unit Price
5. Submit for approval

#### Receiving Goods

1. Open the approved PO
2. Verify goods received match PO
3. Click "Mark as Delivered" button
4. (Optional) Select warehouse
5. Confirm action
6. **System automatically adds stock to inventory**

### For Inventory Department

#### Viewing Stock Movements

1. Navigate to Inventory
2. View stock movements tab
3. Filter by reference_type = "purchase_order"
4. See all PO-related stock additions

#### Verifying PO Stock-In

1. Check stock movement notes: "Received from PO #[number]"
2. Click reference to view original PO
3. Verify quantities match

## Checking Integration Status

### Using Diagnostic Script

The system includes a diagnostic script that checks purchasing-inventory integration:

**File:** `public/diagnostic.php`

Run this script to verify:
- Purchase orders marked as delivered have corresponding stock movements
- Inventory quantities are updated correctly
- No missing stock-in records

### Manual Verification

```sql
-- Check POs without stock movements
SELECT po.id, po.po_number, po.status
FROM purchase_orders po
WHERE po.status = 'delivered'
AND NOT EXISTS (
    SELECT 1 FROM stock_movements sm
    WHERE sm.reference_type = 'purchase_order'
    AND sm.reference_id = po.id
);

-- Check stock movements from POs
SELECT sm.*, p.name AS product_name, po.po_number
FROM stock_movements sm
JOIN products p ON sm.product_id = p.id
JOIN purchase_orders po ON sm.reference_id = po.id
WHERE sm.reference_type = 'purchase_order'
ORDER BY sm.created_at DESC;
```

## Common Issues & Solutions

### Issue 1: Stock Not Added After Marking Delivered

**Possible Causes:**
- Item name in PO doesn't match product name in inventory
- Product doesn't exist in products table
- No warehouse available

**Solution:**
1. Check product names match exactly
2. Add product to inventory if missing
3. Ensure at least one warehouse exists

### Issue 2: Wrong Warehouse Selected

**Cause:** System uses first available warehouse if not specified

**Solution:**
- Always specify warehouse when marking as delivered
- Or ensure default warehouse is set up correctly

### Issue 3: Partial Stock-In

**Cause:** Some items matched, some didn't

**Solution:**
1. Check which items were added to inventory
2. Verify product names for unmatched items
3. Manually add stock for unmatched items if needed

## Best Practices

### For Purchasing Department

1. **Standardize Product Names**
   - Use consistent naming conventions
   - Match inventory product names exactly
   - Create products in inventory before ordering

2. **Verify Before Marking Delivered**
   - Check all items received
   - Verify quantities
   - Inspect quality

3. **Select Correct Warehouse**
   - Don't rely on default warehouse
   - Specify warehouse based on storage location
   - Coordinate with inventory department

### For Inventory Department

1. **Maintain Product Master List**
   - Keep products table up to date
   - Use clear, consistent names
   - Share product list with purchasing

2. **Monitor Stock Movements**
   - Review daily stock-ins from POs
   - Verify quantities match PO documents
   - Report discrepancies immediately

3. **Reconcile Regularly**
   - Compare PO deliveries with stock movements
   - Check for missing stock-ins
   - Run diagnostic script weekly

## Integration with Other Departments

### Logistics Department
- Logistics handles inbound deliveries
- When logistics marks delivery as "delivered", it also triggers stock-in
- Purchasing POs and Logistics deliveries both add to inventory

### Production Department
- Production uses inventory for raw materials (seeds, etc.)
- When production "plants", inventory is deducted
- When production "harvests", inventory is added

### Processing Department
- Processing takes raw materials from inventory
- Finished products are added back to inventory
- All movements are tracked

### Sales Department
- Sales orders deduct from inventory when delivered
- Inventory levels affect sales order fulfillment
- Low stock alerts can trigger new POs

## Summary

✅ **Purchasing-Inventory integration is ALREADY WORKING**

The system automatically:
- Adds stock when PO is marked "delivered"
- Creates stock movement records
- Updates inventory quantities
- Links to PO for traceability

**Key Requirement:** Product names in PO must match product names in inventory exactly.

## Testing Checklist

- [ ] Create a PO with items matching existing products
- [ ] Approve the PO
- [ ] Mark PO as delivered
- [ ] Verify stock movements created
- [ ] Check inventory quantities increased
- [ ] Verify reference links to PO
- [ ] Test with non-matching product names
- [ ] Verify unmatched items are skipped
- [ ] Test warehouse selection
- [ ] Run diagnostic script to verify integration

## Related Documentation

- See `public/diagnostic.php` for integration health checks
- See `public/fix_inventory_integration.php` for retroactive fixes
- See `database/purchasing_migration.sql` for table structures
- See `database/inventory_migration.sql` for inventory tables
