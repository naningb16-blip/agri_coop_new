-- Operational Department Document Numbers
-- Adds document numbers for production records and processing batches

-- Add production_number to production_records
ALTER TABLE production_records
ADD COLUMN IF NOT EXISTS production_number VARCHAR(100) UNIQUE AFTER id;

-- Add processing_number to processing_batches (batch_number already exists, but let's ensure it's unique)
-- The batch_number column already exists, so we just need to ensure it's being used

-- Generate production numbers for existing records
UPDATE production_records
SET production_number = CONCAT('PROD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
WHERE production_number IS NULL;

-- Verify batch_number exists and is populated
UPDATE processing_batches
SET batch_number = CONCAT('BATCH-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
WHERE batch_number IS NULL OR batch_number = '';

SELECT '=== OPERATIONAL DOCUMENT NUMBERS INSTALLED ===' AS status;

-- Verify
SELECT 
    COUNT(*) AS total_production,
    SUM(CASE WHEN production_number IS NOT NULL THEN 1 ELSE 0 END) AS with_prod_number
FROM production_records;

SELECT 
    COUNT(*) AS total_batches,
    SUM(CASE WHEN batch_number IS NOT NULL THEN 1 ELSE 0 END) AS with_batch_number
FROM processing_batches;

SELECT '=== FEATURE READY ===' AS info;
