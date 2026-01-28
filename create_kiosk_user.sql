INSERT INTO employees (first_name, last_name, email, password, role, employee_code, personal_email, dob, doj, designation_id, department_id, status)
SELECT 'Kiosk', 'Admin', 'kiosk@wishluvbuildcon.com', '$2y$10$YourHashedPasswordHere', 'Admin', 'KIOSK001', 'kiosk@wishluvbuildcon.com', '2000-01-01', CURDATE(), 1, 1, 'Active'
WHERE NOT EXISTS (SELECT 1 FROM employees WHERE email = 'kiosk@wishluvbuildcon.com');
