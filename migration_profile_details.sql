USE u743570205_wishluvhrmsapp;

-- Add Profile Details columns to employees table
-- Running these individual ALTER statements is safer to avoid 'Duplicate column' errors if some exist.
-- If a column exists, the statement will fail but others will run if executed individually in some clients, 
-- but here we provide a block. If you get "Duplicate column", remove the specific line.

ALTER TABLE employees
ADD COLUMN fathers_name VARCHAR(100) NULL AFTER last_name,
ADD COLUMN personal_email VARCHAR(100) NULL AFTER email,
ADD COLUMN personal_phone VARCHAR(20) NULL AFTER phone,
ADD COLUMN official_phone VARCHAR(20) NULL AFTER personal_phone,
ADD COLUMN emergency_contact_name VARCHAR(100) NULL,
ADD COLUMN emergency_contact_phone VARCHAR(20) NULL,
ADD COLUMN emergency_contact_relation VARCHAR(50) NULL,
ADD COLUMN pan_number VARCHAR(20) NULL,
ADD COLUMN pan_doc VARCHAR(255) NULL,
ADD COLUMN aadhar_number VARCHAR(20) NULL,
ADD COLUMN aadhar_doc VARCHAR(255) NULL,
ADD COLUMN bank_account_number VARCHAR(50) NULL,
ADD COLUMN bank_ifsc VARCHAR(20) NULL,
ADD COLUMN bank_doc VARCHAR(255) NULL,
ADD COLUMN uan_number VARCHAR(50) NULL,
ADD COLUMN pf_number VARCHAR(50) NULL;
