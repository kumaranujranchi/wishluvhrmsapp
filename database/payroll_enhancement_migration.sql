-- Migration to add new columns to monthly_payroll for LOP, Holidays, and manual deductions (PF, ESI, Other)
ALTER TABLE monthly_payroll
ADD COLUMN holiday_days INT DEFAULT 0 AFTER absent_days,
ADD COLUMN lop_days DECIMAL(4, 1) DEFAULT 0.0 AFTER holiday_days,
ADD COLUMN pf_deduction DECIMAL(10, 2) DEFAULT 0.0 AFTER base_salary,
ADD COLUMN esi_deduction DECIMAL(10, 2) DEFAULT 0.0 AFTER pf_deduction,
ADD COLUMN other_deductions DECIMAL(10, 2) DEFAULT 0.0 AFTER esi_deduction,
ADD COLUMN gross_salary DECIMAL(10, 2) DEFAULT 0.0 AFTER base_salary;
