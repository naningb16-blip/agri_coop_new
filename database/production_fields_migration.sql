-- Add Planting-to-Harvesting fields to production_records
ALTER TABLE production_records
    ADD COLUMN IF NOT EXISTS land_owner        VARCHAR(150)   NULL AFTER farm_location,
    ADD COLUMN IF NOT EXISTS variety           VARCHAR(100)   NULL AFTER land_owner,
    ADD COLUMN IF NOT EXISTS seed_grower_name  VARCHAR(150)   NULL AFTER variety,
    ADD COLUMN IF NOT EXISTS no_of_seed_kgs    DECIMAL(10,2)  DEFAULT 0 AFTER seed_grower_name,
    ADD COLUMN IF NOT EXISTS fertilizer_used   TEXT           NULL AFTER no_of_seed_kgs,
    ADD COLUMN IF NOT EXISTS actual_harvest    DATE           NULL AFTER expected_harvest,
    ADD COLUMN IF NOT EXISTS milling_kgs       DECIMAL(10,2)  DEFAULT 0 AFTER actual_yield,
    ADD COLUMN IF NOT EXISTS bagging_bags      DECIMAL(10,2)  DEFAULT 0 AFTER milling_kgs;
