-- ============================================================
-- Payroll: Itemized deductions (SSS, Pag-ibig, PhilHealth, Other)
-- ============================================================

ALTER TABLE payroll
    ADD COLUMN IF NOT EXISTS sss_pct         DECIMAL(5,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sss_amount      DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS pagibig_pct     DECIMAL(5,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS pagibig_amount  DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS philhealth_pct  DECIMAL(5,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS philhealth_amount DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS other_deductions DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS other_deductions_note VARCHAR(255) DEFAULT NULL;
