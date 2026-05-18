-- Logistics Department Delivery Receipt Enhancement
-- Adds document numbers, unit costs, and total amounts to delivery items

-- Add dr_number (Delivery Receipt Number) to deliveries table
ALTER TABLE deliveries
ADD COLUMN IF NOT EXISTS dr_number VARCHAR(100) UNIQUE AFTER id;

-- Add unit_cost to delivery_items table
ALTER TABLE delivery_items
ADD COLUMN IF NOT EXISTS unit_cost DECIMAL(12,2) DEFAULT 0 AFTER quantity;

-- Add total_amount to delivery_items table
ALTER TABLE delivery_items
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(12,2) DEFAULT 0 AFTER unit_cost;

-- Generate DR numbers for existing deliveries
UPDATE deliveries
SET dr_number = CONCAT('DR-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 4, '0'))
WHERE dr_number IS NULL;

-- Calculate total_amount for existing delivery items (quantity * unit_cost)
UPDATE delivery_items
SET total_amount = quantity * unit_cost
WHERE total_amount = 0 AND unit_cost > 0;

SELECT '=== LOGISTICS DELIVERY RECEIPT ENHANCEMENT INSTALLED ===' AS status;

-- Verify
SELECT 
    COUNT(*) AS total_deliveries,
    SUM(CASE WHEN dr_number IS NOT NULL THEN 1 ELSE 0 END) AS with_dr_number
FROM deliveries;

SELECT 
    COUNT(*) AS total_items,
    SUM(CASE WHEN unit_cost > 0 THEN 1 ELSE 0 END) AS with_unit_cost,
    SUM(CASE WHEN total_amount > 0 THEN 1 ELSE 0 END) AS with_total_amount
FROM delivery_items;

SELECT '=== FEATURE READY ===' AS info;
