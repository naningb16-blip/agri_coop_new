# Fix: Sales Payment Columns Error

## Error Message
```
Fatal error: Unknown column 'payment_status' in 'field list'
```

## Cause
The `sales_payment_integration.sql` migration was not run on the production database, so the payment tracking columns are missing from the `sales_orders` table.

---

## Quick Fix (2 minutes)

### Option 1: Run via Web Browser (Easiest)

1. **Navigate to**:
   ```
   http://your-domain/fix_sales_columns.php
   ```

2. **You should see**:
   ```
   === Sales Payment Columns Fix ===
   
   Connected to database: agri_coop
   
   Executing migration...
   
   Sales payment columns added successfully!
   column_count: 4
   
   === Migration Complete ===
   ```

3. **Delete the script** (for security):
   ```bash
   rm public/fix_sales_columns.php
   ```

4. **Test**: Go to Sales page - should work now!

---

### Option 2: Run via MySQL Command Line

```bash
mysql -u root -p agri_coop < database/fix_sales_payment_columns.sql
```

---

## What This Fix Does

Adds 4 missing columns to `sales_orders` table:

1. **`payment_type`** - ENUM('cash','charge','credit') DEFAULT 'cash'
2. **`payment_status`** - ENUM('unpaid','partial','paid') DEFAULT 'unpaid'
3. **`amount_paid`** - DECIMAL(12,2) DEFAULT 0
4. **`receipt_id`** - INT NULL (links to receipts table)

Also adds indexes for better performance:
- `idx_payment_status`
- `idx_receipt_id`

---

## Verification

After running the fix, verify the columns exist:

```sql
DESCRIBE sales_orders;
```

You should see the new columns:
- payment_type
- payment_status
- amount_paid
- receipt_id

---

## Why This Happened

The `sales_payment_integration.sql` migration file exists in the codebase but wasn't run on the production database. This migration is required for the Sales → Finance integration feature.

---

## Prevention

To prevent this in the future:

1. Always run all migration files when deploying
2. Check `database/` folder for new `.sql` files
3. Use the migration tracker to see which migrations have been run

---

## Related Migrations

If you haven't run these, you should also run them:

1. ✅ `sales_payment_integration.sql` - Sales payment tracking (this fix covers it)
2. ✅ `finance_enhancements.sql` - Finance billing categories
3. ✅ `inventory_low_stock_feature.sql` - Inventory alerts
4. ✅ `logistics_inbound_outbound_feature.sql` - Logistics delivery types

---

## Status

- ✅ Fix script created
- ✅ Pushed to GitHub
- ⏳ Waiting for you to run it

**Next Step**: Run `http://your-domain/fix_sales_columns.php`
