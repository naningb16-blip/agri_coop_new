# Low Stock Alert for Purchasing Department

## Code to Add

Add this code in `app/views/purchasing/index.php` after line 51 (after `</ul>`):

```php

<!-- Low Stock Alert for Purchasing -->
<?php if (!empty($lowStockItems)): ?>
<div class="alert alert-danger mb-4">
    <div class="d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
        <div class="flex-grow-1">
            <h5 class="mb-2"><i class="bi bi-box-seam"></i> Low Stock Alert (<?= count($lowStockItems) ?> items)</h5>
            <p class="mb-2">The following items are below their reorder levels and need restocking:</p>
            <ul class="mb-0">
            <?php foreach ($lowStockItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong>: 
                    Current stock <span class="badge bg-danger"><?= number_format($item['current_stock'], 2) ?> <?= htmlspecialchars($item['unit']) ?></span>
                    (Reorder at: <?= number_format($item['reorder_level'], 2) ?> <?= htmlspecialchars($item['unit']) ?>)
                    — Shortage: <strong><?= number_format($item['shortage'], 2) ?> <?= htmlspecialchars($item['unit']) ?></strong>
                </li>
            <?php endforeach; ?>
            </ul>
            <div class="mt-2">
                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#newPOModal">
                    <i class="bi bi-plus-circle me-1"></i>Create Purchase Order
                </button>
                <a href="<?= BASE_URL ?>/inventory" class="btn btn-sm btn-outline-danger">View Inventory</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
```

## Location

Insert this code between:
- Line 51: `</ul>`
- Line 52: `<!-- Purchase Orders Tab comment -->`

## What It Does

- Shows a red alert box at the top of the purchasing page
- Lists all items below their reorder levels
- Shows current stock, reorder level, and shortage for each item
- Provides quick action buttons to create a PO or view inventory
- Only shows when there are low stock items

## Note

The low stock query is already at the top of the file (lines 2-13), so you only need to add the display code.

## Summary

Now low stock alerts will be visible to:
- ✅ GM Dashboard
- ✅ Inventory Department page
- ✅ Purchasing Department page (after adding this code)

All three departments can see which items need restocking!
