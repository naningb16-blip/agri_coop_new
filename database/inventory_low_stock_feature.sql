-- Inventory Low Stock Notification Feature
-- Adds reorder level tracking and low stock alerts

USE agri_coop;

-- Add reorder_level column to products table
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS reorder_level DECIMAL(12,2) DEFAULT 10.00 AFTER unit,
ADD COLUMN IF NOT EXISTS max_stock_level DECIMAL(12,2) DEFAULT NULL AFTER reorder_level;

-- Add low_stock_notified column to track if notification was sent
ALTER TABLE products
ADD COLUMN IF NOT EXISTS low_stock_notified_at TIMESTAMP NULL AFTER max_stock_level;

-- Create index for faster low stock queries
CREATE INDEX IF NOT EXISTS idx_products_reorder ON products(reorder_level);

-- Update existing products with default reorder level (10 units)
UPDATE products SET reorder_level = 10.00 WHERE reorder_level IS NULL OR reorder_level = 0;

-- Verification query
SELECT 
    '=== LOW STOCK FEATURE INSTALLED ===' AS status,
    COUNT(*) AS total_products,
    SUM(CASE WHEN reorder_level > 0 THEN 1 ELSE 0 END) AS products_with_reorder_level
FROM products;

-- Show products that are currently low on stock
SELECT 
    '=== CURRENT LOW STOCK PRODUCTS ===' AS info;

SELECT 
    p.id,
    p.name,
    p.unit,
    COALESCE(SUM(i.quantity), 0) AS current_stock,
    p.reorder_level,
    p.reorder_level - COALESCE(SUM(i.quantity), 0) AS shortage
FROM products p
LEFT JOIN inventory i ON p.id = i.product_id
GROUP BY p.id, p.name, p.unit, p.reorder_level
HAVING current_stock < p.reorder_level
ORDER BY shortage DESC;
