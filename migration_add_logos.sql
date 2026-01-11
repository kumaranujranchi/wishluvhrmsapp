-- Add logo column to departments and designations
ALTER TABLE departments ADD COLUMN logo VARCHAR(255) NULL AFTER description;
ALTER TABLE designations ADD COLUMN logo VARCHAR(255) NULL AFTER name;
