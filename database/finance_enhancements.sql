-- Finance Department Enhancements
-- 1. Purchase Orders visibility in Finance
-- 2. Categorized monthly billing expenses

USE agri_coop;

-- ============================================================
-- Enhancement 1: Add expense categories for bills
-- ============================================================

-- Add predefined expense categories
ALTER TABLE expenses 
MODIFY COLUMN category ENUM(
    'utilities_electric',
    'utilities_water', 
    'utilities_internet',
    'utilities_phone',
    'rent',
    'supplies',
    'maintenance',
    'transportation',
    'professional_fees',
    'insurance',
    'taxes',
    'salaries',
    'other'
) DEFAULT 'other';

-- Add billing period tracking
ALTER TABLE expenses
ADD COLUMN IF NOT EXISTS billing_month VARCHAR(7) NULL COMMENT 'Format: YYYY-MM' AFTER expense_date,
ADD COLUMN IF NOT EXISTS due_date DATE NULL AFTER billing_month,
ADD COLUMN IF NOT EXISTS vendor_name VARCHAR(150) NULL AFTER description,
ADD COLUMN IF NOT EXISTS account_number VARCHAR(100) NULL AFTER vendor_name;

-- Create index for faster billing queries
CREATE INDEX IF NOT EXISTS idx_expenses_billing ON expenses(billing_month, category);
CREATE INDEX IF NOT EXISTS idx_expenses_category ON expenses(category);

-- ============================================================
-- Enhancement 2: Purchase Orders in Finance View
-- ============================================================

-- POs are already in purchase_orders table, just need to query them
-- Add index for faster finance queries
CREATE INDEX IF NOT EXISTS idx_po_status_date ON purchase_orders(status, order_date);
CREATE INDEX IF NOT EXISTS idx_po_total ON purchase_orders(total_amount);

-- ============================================================
-- Sample Data: Predefined Expense Categories
-- ============================================================

-- Insert sample billing categories (for reference)
INSERT IGNORE INTO expense_categories (name, description, type) VALUES
('Electric Bill', 'Monthly electricity expenses', 'utilities_electric'),
('Water Bill', 'Monthly water utility expenses', 'utilities_water'),
('Internet Bill', 'Monthly internet service', 'utilities_internet'),
('Phone Bill', 'Monthly telephone/mobile service', 'utilities_phone'),
('Office Rent', 'Monthly office/warehouse rent', 'rent'),
('Office Supplies', 'Stationery, consumables', 'supplies'),
('Equipment Maintenance', 'Repairs and maintenance', 'maintenance'),
('Fuel & Transportation', 'Vehicle fuel and transport costs', 'transportation'),
('Professional Fees', 'Consultants, legal, accounting', 'professional_fees'),
('Insurance Premiums', 'Business insurance payments', 'insurance'),
('Taxes & Licenses', 'Government fees and taxes', 'taxes'),
('Employee Salaries', 'Monthly salary payments', 'salaries'),
('Miscellaneous', 'Other operating expenses', 'other')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Note: expense_categories table may not exist, so this is optional
-- The main categories are in the ENUM field

-- ============================================================
-- Verification Queries
-- ============================================================

SELECT '=== EXPENSE CATEGORIES UPDATED ===' AS status;

-- Show available expense categories
SELECT 
    'utilities_electric' AS category, 'Electric Bill' AS label
UNION ALL SELECT 'utilities_water', 'Water Bill'
UNION ALL SELECT 'utilities_internet', 'Internet Bill'
UNION ALL SELECT 'utilities_phone', 'Phone Bill'
UNION ALL SELECT 'rent', 'Rent'
UNION ALL SELECT 'supplies', 'Office Supplies'
UNION ALL SELECT 'maintenance', 'Maintenance'
UNION ALL SELECT 'transportation', 'Transportation'
UNION ALL SELECT 'professional_fees', 'Professional Fees'
UNION ALL SELECT 'insurance', 'Insurance'
UNION ALL SELECT 'taxes', 'Taxes'
UNION ALL SELECT 'salaries', 'Salaries'
UNION ALL SELECT 'other', 'Other';

-- Show current month's bills by category
SELECT 
    '=== CURRENT MONTH BILLS ===' AS info;

SELECT 
    category,
    COUNT(*) AS count,
    SUM(amount) AS total_amount,
    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) AS pending_amount
FROM expenses
WHERE billing_month = DATE_FORMAT(CURDATE(), '%Y-%m')
GROUP BY category;

-- Show purchase orders for finance tracking
SELECT 
    '=== PURCHASE ORDERS FOR FINANCE ===' AS info;

SELECT 
    po.id,
    po.po_number,
    po.order_date,
    s.name AS supplier,
    po.total_amount,
    po.status,
    CASE 
        WHEN po.status = 'delivered' THEN 'Ready for Payment'
        WHEN po.status = 'approved' THEN 'Awaiting Delivery'
        ELSE 'Pending Approval'
    END AS finance_status
FROM purchase_orders po
JOIN suppliers s ON po.supplier_id = s.id
WHERE po.status IN ('approved', 'delivered')
ORDER BY po.order_date DESC
LIMIT 10;

SELECT '=== FINANCE ENHANCEMENTS COMPLETE ===' AS status;
