# Remaining Features - Implementation Guide

## Overview

This document outlines the remaining features that need UI implementation. The database migrations are ready, but the UI updates are pending.

---

## Feature Status Summary

| Feature | Database | Controller | UI | Status |
|---------|----------|------------|-----|--------|
| Sales → Finance | ✅ | ✅ | ✅ | **COMPLETE** |
| Purchasing → Finance | ✅ | ✅ | ✅ | **COMPLETE** |
| Finance Billing Categories | ✅ | ✅ | ✅ | **COMPLETE** |
| Inventory Low Stock | ✅ | ⏳ | ⏳ | **PENDING** |
| Logistics Inbound/Outbound | ✅ | ⏳ | ⏳ | **PENDING** |

---

## 1. Inventory Low Stock Notifications ⏳

### Database Status: ✅ READY
Migration file: `database/inventory_low_stock_feature.sql`

**What it adds**:
- `reorder_level` column to products table
- `max_stock_level` column (optional)
- `low_stock_notified_at` column (tracks notifications)
- Indexes for performance

### What Needs Implementation:

#### A. Update Product Form
**File**: `app/views/inventory/index.php`

Add reorder level field to product creation/edit form:
```php
<div class="col-md-6">
    <label class="form-label">Reorder Level</label>
    <input type="number" name="reorder_level" class="form-control" 
           step="0.01" min="0" value="10.00" 
           placeholder="Alert when stock falls below this level">
    <small class="text-muted">System will notify when stock is below this level</small>
</div>
```

#### B. Update InventoryController
**File**: `app/controllers/InventoryController.php`

Update `saveProduct` method to include reorder_level:
```php
public function saveProduct(): void {
    // ... existing code ...
    $data = [
        'name'          => trim($_POST['name'] ?? ''),
        'category'      => trim($_POST['category'] ?? ''),
        'unit'          => trim($_POST['unit'] ?? ''),
        'description'   => trim($_POST['description'] ?? ''),
        'reorder_level' => (float)($_POST['reorder_level'] ?? 10.00), // NEW
    ];
    // ... rest of code ...
}
```

#### C. Add Low Stock Alert Section
**File**: `app/views/inventory/index.php`

Add alert box at top of page (only for inventory users):
```php
<?php if ($_SESSION['user']['role'] === 'inventory'): ?>
<?php
$lowStock = $this->db->fetchAll(
    "SELECT p.id, p.name, p.unit, p.reorder_level,
            COALESCE(SUM(i.quantity), 0) AS current_stock,
            p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
     FROM products p
     LEFT JOIN inventory i ON p.id = i.product_id
     GROUP BY p.id
     HAVING current_stock < p.reorder_level
     ORDER BY shortage DESC"
);
?>
<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning mb-3">
    <h5><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</h5>
    <p class="mb-2">The following products are below reorder level:</p>
    <ul class="mb-0">
        <?php foreach ($lowStock as $item): ?>
        <li>
            <strong><?= htmlspecialchars($item['name']) ?></strong>: 
            Current stock: <?= number_format($item['current_stock'], 2) ?> <?= $item['unit'] ?> 
            (Reorder level: <?= number_format($item['reorder_level'], 2) ?> <?= $item['unit'] ?>)
            <span class="badge bg-danger">Shortage: <?= number_format($item['shortage'], 2) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php endif; ?>
```

#### D. Add Low Stock Badge in Stock Table
Update stock table to show low stock indicator:
```php
<td>
    <?= number_format($s['quantity'], 2) ?> <?= $s['unit'] ?>
    <?php if ($s['quantity'] < $s['reorder_level']): ?>
    <span class="badge bg-danger ms-1">Low Stock</span>
    <?php endif; ?>
</td>
```

### Testing Steps:
1. Run migration: `mysql -u root -p agri_coop < database/inventory_low_stock_feature.sql`
2. Create/edit product with reorder level = 50
3. Reduce stock below 50
4. Login as inventory user
5. Verify low stock alert appears
6. Verify badge shows in stock table

---

## 2. Logistics Inbound/Outbound ⏳

### Database Status: ✅ READY
Migration file: `database/logistics_inbound_outbound_feature.sql`

**What it adds**:
- `delivery_type` ENUM column ('inbound', 'outbound')
- `warehouse_id` column for inbound deliveries
- Updates existing deliveries based on reference_type
- Indexes for performance

### What Needs Implementation:

#### A. Update New Delivery Modal
**File**: `app/views/logistics/index.php`

The modal already has reference type selection, but we need to clarify the labels:
```php
<select id="dlvRefType" class="form-select" required onchange="updateRefDocs()">
    <option value="">Select type</option>
    <option value="purchase_order">Purchase Order (Inbound to Warehouse)</option>
    <option value="sales_order">Sales Order (Outbound to Customer)</option>
</select>
```

Add warehouse selector for inbound deliveries:
```php
<div class="col-md-6" id="warehouseSelector" style="display:none;">
    <label class="form-label">Destination Warehouse <span class="text-danger">*</span></label>
    <select id="dlvWarehouse" class="form-select">
        <option value="">Select warehouse</option>
        <?php foreach ($warehouses as $w): ?>
        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<script>
function updateRefDocs() {
    const type = document.getElementById('dlvRefType').value;
    const warehouseDiv = document.getElementById('warehouseSelector');
    
    // Show warehouse selector for inbound (purchase orders)
    if (type === 'purchase_order') {
        warehouseDiv.style.display = 'block';
    } else {
        warehouseDiv.style.display = 'none';
    }
    
    // ... existing code for updating reference docs ...
}
</script>
```

#### B. Update Deliveries Table
**File**: `app/views/logistics/index.php`

The table already shows type, but let's make it clearer:
```php
<td>
    <?php if ($d['delivery_type'] === 'inbound'): ?>
    <span class="badge bg-success">
        <i class="bi bi-arrow-down-circle me-1"></i>Inbound
    </span>
    <small class="text-muted d-block">To Warehouse</small>
    <?php else: ?>
    <span class="badge bg-primary">
        <i class="bi bi-arrow-up-circle me-1"></i>Outbound
    </span>
    <small class="text-muted d-block">To Customer</small>
    <?php endif; ?>
</td>
```

#### C. Update LogisticsController
**File**: `app/controllers/LogisticsController.php`

Update `create` method to handle warehouse_id:
```php
public function create(): void {
    // ... existing code ...
    $warehouseId = (int)($_POST['warehouse_id'] ?? 0) ?: null;
    
    // Determine delivery type
    $deliveryType = $refType === 'purchase_order' ? 'inbound' : 'outbound';
    
    $id = $this->db->insert(
        "INSERT INTO deliveries
            (reference_type, reference_id, delivery_type, warehouse_id, driver_name, vehicle_plate, origin, destination, dispatch_date, notes, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,'pending')",
        [$refTypeDb, $refId, $deliveryType, $warehouseId, $driver, $plate, $origin, $dest, $dispatch ?: null, $notes],
        'sisississs'
    );
    // ... rest of code ...
}
```

#### D. Update submitDelivery JavaScript
**File**: `app/views/logistics/index.php`

Add warehouse_id to form submission:
```php
function submitDelivery() {
    // ... existing code ...
    const warehouseId = document.getElementById('dlvWarehouse')?.value || '';
    
    const fd = new FormData();
    // ... existing fields ...
    if (warehouseId) fd.append('warehouse_id', warehouseId);
    // ... rest of code ...
}
```

### Testing Steps:
1. Run migration: `mysql -u root -p agri_coop < database/logistics_inbound_outbound_feature.sql`
2. Create inbound delivery (Purchase Order)
   - Select "Purchase Order (Inbound to Warehouse)"
   - Select destination warehouse
   - Verify delivery_type = 'inbound'
3. Create outbound delivery (Sales Order)
   - Select "Sales Order (Outbound to Customer)"
   - Verify delivery_type = 'outbound'
4. Check deliveries table shows correct badges
5. Verify inbound deliveries add stock to warehouse
6. Verify outbound deliveries deduct stock from warehouse

---

## Migration Commands

Run all pending migrations:

```bash
# Finance enhancements (DONE - but run if not yet applied)
mysql -u root -p agri_coop < database/finance_enhancements.sql

# Inventory low stock (PENDING)
mysql -u root -p agri_coop < database/inventory_low_stock_feature.sql

# Logistics inbound/outbound (PENDING)
mysql -u root -p agri_coop < database/logistics_inbound_outbound_feature.sql
```

Or use the web interface:
```
http://your-domain/migrate.php
```

---

## Priority Order

1. **✅ Finance Enhancements** - COMPLETE
   - Test and deploy immediately

2. **⏳ Inventory Low Stock** - NEXT
   - Simple UI updates
   - High value for inventory management
   - Estimated time: 1-2 hours

3. **⏳ Logistics Inbound/Outbound** - AFTER
   - Clarifies delivery direction
   - Improves warehouse management
   - Estimated time: 1-2 hours

---

## Summary

### Completed ✅
- Finance billing categories
- Sales receipts → Finance
- Purchase orders → Finance
- Database migrations for all features

### Pending ⏳
- Inventory low stock UI
- Logistics inbound/outbound UI

### Estimated Total Time
- Inventory: 1-2 hours
- Logistics: 1-2 hours
- Testing: 1 hour
- **Total: 3-5 hours**

---

**Last Updated**: May 4, 2026  
**Status**: Finance Complete, Inventory & Logistics Pending UI
