# Feature Enhancements Plan

## Overview
This document outlines the requested feature enhancements across multiple departments.

## Feature Status

### 1. Sales Department - Cash Receipts & Charge Invoices ✅ COMPLETE
**Status**: Already implemented (Task 1 from previous work)

**Features**:
- Payment type selection (Cash/Charge/Credit) when creating sales orders
- Automatic receipt generation (REC-YYYYMMDD-XXXX format)
- Payment status tracking (Unpaid → Partial → Paid)
- Receipts automatically appear in Finance department
- Journal entries created automatically

**Files**:
- `database/sales_payment_integration.sql`
- `app/controllers/SalesController.php`
- `SALES_PAYMENT_INTEGRATION.md`
- `SALES_PAYMENT_USER_GUIDE.md`

---

### 2. Purchasing Department - Auto Add to Inventory ✅ COMPLETE
**Status**: Already implemented (Task 2 from previous work)

**Features**:
- When PO is marked "delivered", stock automatically added to inventory
- System matches PO items to products by exact name match
- Creates stock movement records with reference to PO
- Idempotent (won't duplicate if run multiple times)

**Files**:
- `app/controllers/PurchasingController.php`
- `PURCHASING_INVENTORY_INTEGRATION.md`
- `PURCHASING_INVENTORY_USER_GUIDE.md`

---

### 3. Inventory Department - Low Stock Notifications ❌ TO IMPLEMENT
**Status**: New feature needed

**Requirements**:
- Notify inventory department users when stock level is low
- Only inventory users should receive these notifications
- Define "low stock" threshold (e.g., below reorder level)

**Implementation Plan**:
1. Add `reorder_level` field to products table
2. Create low stock check function
3. Generate notifications when stock falls below reorder level
4. Add low stock indicator in inventory view
5. Send notifications only to inventory_user role

**Estimated Files to Modify**:
- `database/inventory_low_stock_migration.sql` (new)
- `app/controllers/InventoryController.php`
- `app/models/InventoryModel.php`
- `app/views/inventory/index.php`
- `core/NotificationHelper.php`

---

### 4. Logistics Department - Inbound/Outbound Clarification ⚠️ NEEDS CLARIFICATION
**Status**: Partially implemented, needs enhancement

**Current Implementation**:
- Deliveries linked to POs (inbound) or SOs (outbound)
- PO deliveries: Stock added when marked "delivered"
- SO deliveries: Stock deducted when "in_transit"

**Requested Enhancement**:
- **Inbound**: Delivery TO warehouse (from supplier/production)
- **Outbound**: Delivery TO customer (from warehouse)

**Questions to Clarify**:
1. Should we add an explicit "delivery_type" field (inbound/outbound)?
2. Or keep current system where:
   - PO-linked deliveries = Inbound (from supplier to warehouse)
   - SO-linked deliveries = Outbound (from warehouse to customer)
3. Should inbound deliveries also support production/processing sources?

**Proposed Implementation**:
- Add `delivery_type` ENUM('inbound', 'outbound') to deliveries table
- Inbound: Stock IN to warehouse when delivered
- Outbound: Stock OUT from warehouse when dispatched
- Update UI to show delivery type clearly
- Add warehouse selection for inbound deliveries

**Estimated Files to Modify**:
- `database/logistics_inbound_outbound_migration.sql` (new)
- `app/controllers/LogisticsController.php`
- `app/views/logistics/index.php`
- `app/views/logistics/detail.php`

---

## Implementation Priority

### Phase 1: Inventory Low Stock Notifications (High Priority)
**Why**: Critical for inventory management and preventing stockouts

**Steps**:
1. Add reorder_level to products
2. Create low stock detection
3. Implement notifications
4. Update inventory UI

**Estimated Time**: 2-3 hours

---

### Phase 2: Logistics Inbound/Outbound Enhancement (Medium Priority)
**Why**: Improves clarity and warehouse management

**Steps**:
1. Clarify requirements with user
2. Add delivery_type field
3. Update create delivery flow
4. Update stock movement logic
5. Update UI labels

**Estimated Time**: 3-4 hours

---

## Technical Considerations

### Inventory Low Stock
- **Threshold**: Should be configurable per product
- **Notification Frequency**: Once per day to avoid spam
- **Recipients**: Only users with `inventory_user` role
- **Notification Method**: In-app notifications + optional email

### Logistics Inbound/Outbound
- **Stock Movement**: Must be idempotent
- **Warehouse Selection**: Required for inbound deliveries
- **Approval Flow**: Outbound may need approval, inbound may not
- **Receipt Generation**: Both types should support delivery receipts

---

## Next Steps

1. **Confirm Requirements**: Verify logistics inbound/outbound requirements
2. **Implement Low Stock**: Start with inventory notifications
3. **Test Integration**: Ensure all features work together
4. **Update Documentation**: Create user guides for new features
5. **Deploy**: Run migrations and test in production

---

## Questions for User

### Inventory Low Stock
1. What should be the default reorder level? (e.g., 10 units, 20% of max stock)
2. Should notifications be sent daily, or immediately when stock drops?
3. Should there be different thresholds for different product categories?

### Logistics Inbound/Outbound
1. Should we add explicit "Inbound" and "Outbound" buttons in the UI?
2. For inbound deliveries, what are the possible sources?
   - Suppliers (from POs) ✓
   - Production/Processing ✓
   - Transfers from other warehouses?
   - Returns from customers?
3. Should inbound deliveries require approval?
4. Should we track which warehouse receives inbound deliveries?

---

## Summary

- ✅ Sales cash receipts: **DONE**
- ✅ Purchasing auto-inventory: **DONE**
- ❌ Inventory low stock: **TO IMPLEMENT**
- ⚠️ Logistics inbound/outbound: **NEEDS CLARIFICATION**

Ready to proceed with implementation once requirements are confirmed!
