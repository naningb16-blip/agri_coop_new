-- ============================================================
-- Payroll: Labor vs Management split
-- ============================================================
USE agri_coop;

-- Add employee type to employees table
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS employee_type ENUM('labor','management') DEFAULT 'labor';

-- Add employee_type to payroll for easy filtering
ALTER TABLE payroll
    ADD COLUMN IF NOT EXISTS employee_type ENUM('labor','management') DEFAULT 'labor',
    ADD COLUMN IF NOT EXISTS pay_type      ENUM('daily','monthly','piece_rate') DEFAULT 'monthly',
    ADD COLUMN IF NOT EXISTS days_worked   DECIMAL(5,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS daily_rate    DECIMAL(12,2) DEFAULT 0;
