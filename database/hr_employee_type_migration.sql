ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS employee_type ENUM('labor','management') DEFAULT 'labor';
