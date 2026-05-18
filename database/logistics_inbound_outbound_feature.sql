-- Logistics Inbound/Outbound Delivery Feature
-- Clarifies delivery direction and improves warehouse management

USE agri_coop;

-- Add delivery_type column to deliveries table
ALTER TABLE deliveries
ADD COLUMN IF NOT EXISTS delivery_type ENUM('inbound', 'outbound') DEFAULT 'outbound' AFTER reference_id;

-- Add warehouse_id for inbound deliveries (which warehouse receives the goods)
ALTER TABLE deliveries
ADD COLUMN IF NOT EXISTS warehouse_id INT NULL AFTER delivery_type,
ADD CONSTRAINT fk_deliveries_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id);

-- Update existing deliveries based on reference_type
-- Purchase orders = Inbound (goods coming TO warehouse)
UPDATE deliveries 
SET delivery_type = 'inbound' 
WHERE reference_type IN ('purchase', 'purchase_order');

-- Sales orders = Outbound (goods going FROM warehouse to customer)
UPDATE deliveries 
SET delivery_type = 'outbound' 
WHERE reference_type IN ('sale', 'sales_order');

-- Set default warehouse for existing inbound deliveries
UPDATE deliveries d
SET warehouse_id = (SELECT id FROM warehouses LIMIT 1)
WHERE delivery_type = 'inbound' AND warehouse_id IS NULL;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_deliveries_type ON deliveries(delivery_type);

-- Verification
SELECT 
    '=== INBOUND/OUTBOUND FEATURE INSTALLED ===' AS status;

SELECT 
    delivery_type,
    COUNT(*) AS count,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
    SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) AS in_transit,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
FROM deliveries
GROUP BY delivery_type;

SELECT '=== FEATURE READY ===' AS info;
