-- Employee Deactivation Migration
-- Run this SQL on your Hostinger Database via phpMyAdmin or SQL console

USE u743570205_wishluvhrmsapp;

-- 1. Add 'status' column to employees table
-- This allows categorizing employees as Active or Deactivated
ALTER TABLE employees 
ADD COLUMN status ENUM('Active', 'Deactivated') DEFAULT 'Active' 
AFTER reporting_manager_id;

-- 2. (Optional) Index for performance on status-based filtering
CREATE INDEX idx_employee_status ON employees(status);
