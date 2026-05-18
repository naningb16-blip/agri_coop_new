# Operational Department Approval Workflow

## What Changed

The Operational Department now requires **GM approval** for all production records and processing batches before work can begin.

## New Workflow

### Production Records (Planting)

**Before:**
1. Operational user creates production record → Status: "planned"
2. User can immediately start planting

**After:**
1. Operational user creates production record → Status: "planned" + Approval request created
2. **GM must approve** the production record
3. After GM approval, operational user can update status to "planted" and begin work

### Processing Batches

**Before:**
1. Operational user creates processing batch → Status: "pending"
2. User can immediately start processing stages

**After:**
1. Operational user creates processing batch → Status: "pending" + Approval request created
2. **GM must approve** the processing batch
3. After GM approval, status changes to "in_progress"
4. Operational user can then mark stages (drying, sorting, shelling, etc.)

## How It Works

### For Operational Users

1. **Create Production Record:**
   - Go to Operational → Production tab
   - Click "New Production Record"
   - Fill in farmer, product, area, expected yield
   - Click "Create"
   - ✅ Record created and **submitted for GM approval**

2. **Create Processing Batch:**
   - Go to Operational → Processing tab
   - Click "New Batch"
   - Fill in product, stages, input quantity, warehouse
   - Click "Create Batch"
   - ✅ Batch created and **submitted for GM approval**

3. **Wait for GM Approval:**
   - You'll see "Pending GM Approval" status
   - Cannot proceed until GM approves

4. **After GM Approval:**
   - **Production:** Update status to "planted" and begin work
   - **Processing:** Mark stages as completed (drying, sorting, etc.)

### For GM Users

1. **View Approval Requests:**
   - Go to **Approvals** module
   - See all pending operational requests:
     - Production records (planting requests)
     - Processing batches (processing requests)

2. **Review Request:**
   - Click on request to see details
   - Review:
     - Production: Farmer, product, area, expected yield
     - Processing: Product, stages, input quantity, warehouse

3. **Approve or Reject:**
   - Click "Approve" to allow work to proceed
   - Click "Reject" if changes needed
   - Add remarks if needed

4. **After Approval:**
   - **Production:** Status remains "planned", operational user can now plant
   - **Processing:** Status changes to "in_progress", operational user can mark stages

## Database Changes

### New Approval Chain

```sql
INSERT INTO approval_chains (module, step_order, approver_role, label, is_gm_step) VALUES
('operational', 1, 'gm', 'General Manager Approval', 1);
```

### Approval Requests Created

**Production Records:**
- Module: `operational`
- Reference Type: `production_record`
- Reference ID: Production record ID
- Title: "Production: [Product] - [Farmer]"
- Description: Area and expected yield details

**Processing Batches:**
- Module: `operational`
- Reference Type: `processing_batch`
- Reference ID: Batch ID
- Title: "Processing Batch [BATCH-NUMBER]"
- Description: Product, stages, and input quantity

## Code Changes

### OperationalController.php

**1. createProduction() - Added approval request:**
```php
$this->approvalModel->createRequest([
    'module'         => 'operational',
    'reference_type' => 'production_record',
    'reference_id'   => $id,
    'title'          => "Production: " . $product['name'] . " - " . $farmer['full_name'],
    'description'    => "Area: X ha | Expected Yield: Y kg",
], $_SESSION['user_id']);
```

**2. createProcessing() - Already had approval request ✓**

**3. New Methods Added:**
- `approveProduction()` - GM approves production records
- `approveProcessing()` - GM approves processing batches

## Installation

Run the fix script to add the approval chain:

```
https://agri-coop.onrender.com/fix_operational_approvals.php
```

This will:
1. Add GM approval chain for operational module
2. Verify existing approval requests
3. Show statistics

## Testing

### Test Production Approval

1. **As Operational User:**
   - Create new production record
   - Note the record ID
   - Verify you see "Pending GM Approval"

2. **As GM:**
   - Go to Approvals module
   - Find the production request
   - Click to view details
   - Click "Approve"

3. **As Operational User:**
   - Go back to production record
   - Update status to "planted"
   - Should work now!

### Test Processing Approval

1. **As Operational User:**
   - Create new processing batch
   - Note the batch number
   - Verify status is "pending"

2. **As GM:**
   - Go to Approvals module
   - Find the processing batch request
   - Click "Approve"

3. **As Operational User:**
   - Go back to processing batch detail
   - Mark first stage as completed
   - Should work now!

## Benefits

✅ **Better Control:** GM oversees all operational activities  
✅ **Resource Planning:** GM can review before inventory is used  
✅ **Cost Management:** GM approves before expenses are incurred  
✅ **Quality Assurance:** GM ensures proper planning before execution  
✅ **Audit Trail:** All operational activities have approval records  

## Troubleshooting

### "Approval chain not found"
- Run `fix_operational_approvals.php` to add the approval chain

### "GM doesn't see operational requests"
- Check if approval requests were created (check `approval_requests` table)
- Verify module is 'operational' and reference_type is correct

### "Cannot update status after approval"
- Check if approval status is 'approved' in `approval_requests` table
- Verify GM actually clicked "Approve" button

---

**Last Updated:** May 5, 2026  
**System Version:** Agricultural Cooperative ERP v1.0
