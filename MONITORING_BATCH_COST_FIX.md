# Monitoring Batch Cost Foreign Key Error Fix

## Issue
When trying to add batch costs in the Monitoring module, users encountered a foreign key constraint error:
```
Cannot add or update a child row: a foreign key constraint fails 
("defaultdb"."batch_costs", CONSTRAINT "batch_costs_ibfk_1" 
FOREIGN KEY ("batch_id") REFERENCES "processing_batches" ("id"))
```

## Root Cause
The form allowed users to manually enter a batch ID number, which could result in:
1. Entering an invalid batch ID that doesn't exist
2. Entering a batch ID that was deleted
3. Typos in the batch ID

## Solution

### 1. Added Batch Validation in Controller
Updated `MonitoringController::addBatchCost()` to validate that the batch exists before inserting:

```php
// Validate that the batch exists
$batch = $this->db->fetchOne(
    "SELECT id, batch_number FROM processing_batches WHERE id = ?",
    [$batchId], 'i'
);

if (!$batch) {
    $this->json(['success' => false, 'message' => 'Invalid batch ID. Batch does not exist.']);
}
```

### 2. Changed Input to Dropdown
Replaced the manual batch ID input with a dropdown that shows only valid batches:

**Before:**
```html
<input type="number" name="batch_id" placeholder="Processing Batch ID" required>
```

**After:**
```html
<select name="batch_id" class="form-select" required>
    <option value="">-- Select Batch --</option>
    <?php foreach ($availableBatches as $batch): ?>
    <option value="<?= $batch['id'] ?>">
        Batch #<?= $batch['batch_number'] ?> - <?= $batch['product_name'] ?> 
        (<?= $batch['status'] ?>) - <?= date('M d, Y', $batch['created_at']) ?>
    </option>
    <?php endforeach; ?>
</select>
```

### 3. Added Available Batches Query
Updated the controller to fetch available batches:

```php
$availableBatches = $this->db->fetchAll(
    "SELECT pb.id, pb.batch_number, pb.status, p.name AS product_name, pb.created_at
     FROM processing_batches pb
     JOIN products p ON pb.product_id = p.id
     WHERE pb.status IN ('in_progress', 'completed')
     ORDER BY pb.created_at DESC
     LIMIT 100"
);
```

## Benefits

✅ **Prevents Foreign Key Errors** - Users can only select valid batches  
✅ **Better UX** - No need to remember batch IDs  
✅ **Shows Context** - Displays batch number, product, status, and date  
✅ **Validation** - Server-side check ensures batch exists  
✅ **User Feedback** - Shows warning if no batches are available  

## Files Modified

1. `app/controllers/MonitoringController.php`
   - Added batch validation in `addBatchCost()` method
   - Added `availableBatches` query in `index()` method

2. `app/views/monitoring/index.php`
   - Changed batch input from text field to dropdown
   - Added helpful message when no batches available

## Testing

1. **Test Valid Batch:**
   - Go to Monitoring module
   - Click "Add Batch Cost"
   - Select a batch from dropdown
   - Enter cost details
   - Submit → Should succeed

2. **Test No Batches:**
   - If no processing batches exist
   - Modal should show warning message
   - Submit button should be disabled (by required attribute)

3. **Test Invalid Batch (API):**
   - Try to POST with invalid batch_id
   - Should return error: "Invalid batch ID. Batch does not exist."

## Related Modules

This fix ensures data integrity between:
- **Monitoring Module** - Where costs are recorded
- **Processing Module** - Where batches are created
- **batch_costs table** - Foreign key to processing_batches

## Prevention

To prevent similar issues in the future:
1. Always use dropdowns for foreign key references
2. Add server-side validation for all foreign keys
3. Provide clear error messages to users
4. Show contextual information in dropdowns

---
**Date:** May 6, 2026  
**Status:** Fixed and tested
