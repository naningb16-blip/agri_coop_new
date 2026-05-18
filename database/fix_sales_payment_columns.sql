-- Fix: Add missing payment columns to sales_orders table
-- This should have been added by sales_payment_integration.sql

USE agri_coop;

-- Check if columns exist, if not add them
SET @dbname = DATABASE();
SET @tablename = 'sales_orders';

-- Add payment_type column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND COLUMN_NAME = 'payment_type';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE sales_orders ADD COLUMN payment_type ENUM(''cash'',''charge'',''credit'') DEFAULT ''cash'' AFTER total_amount',
    'SELECT ''Column payment_type already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_status column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND COLUMN_NAME = 'payment_status';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE sales_orders ADD COLUMN payment_status ENUM(''unpaid'',''partial'',''paid'') DEFAULT ''unpaid'' AFTER payment_type',
    'SELECT ''Column payment_status already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add amount_paid column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND COLUMN_NAME = 'amount_paid';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE sales_orders ADD COLUMN amount_paid DECIMAL(12,2) DEFAULT 0 AFTER payment_status',
    'SELECT ''Column amount_paid already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add receipt_id column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND COLUMN_NAME = 'receipt_id';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE sales_orders ADD COLUMN receipt_id INT NULL AFTER amount_paid',
    'SELECT ''Column receipt_id already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing sales orders to have default values
UPDATE sales_orders 
SET payment_type = COALESCE(payment_type, 'cash'),
    payment_status = COALESCE(payment_status, 'unpaid'),
    amount_paid = COALESCE(amount_paid, 0)
WHERE payment_status IS NULL OR payment_type IS NULL;

-- Add indexes if they don't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND INDEX_NAME = 'idx_payment_status';

SET @query = IF(@index_exists = 0,
    'ALTER TABLE sales_orders ADD KEY idx_payment_status (payment_status)',
    'SELECT ''Index idx_payment_status already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND INDEX_NAME = 'idx_receipt_id';

SET @query = IF(@index_exists = 0,
    'ALTER TABLE sales_orders ADD KEY idx_receipt_id (receipt_id)',
    'SELECT ''Index idx_receipt_id already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the columns were added
SELECT 
    'Sales payment columns added successfully!' AS status,
    COUNT(*) AS column_count
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND COLUMN_NAME IN ('payment_type', 'payment_status', 'amount_paid', 'receipt_id');

-- Show the structure
DESCRIBE sales_orders;
