-- Create holidays table for managing company holidays
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    holiday_type ENUM('single', 'range') DEFAULT 'single',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (is_active)
);

-- Insert some default holidays for 2026
INSERT INTO holidays (title, description, start_date, end_date, holiday_type) VALUES
('New Year', 'New Year Celebration', '2026-01-01', '2026-01-01', 'single'),
('Republic Day', 'Republic Day of India', '2026-01-26', '2026-01-26', 'single'),
('Holi', 'Festival of Colors', '2026-03-14', '2026-03-14', 'single'),
('Good Friday', 'Good Friday', '2026-04-03', '2026-04-03', 'single'),
('Independence Day', 'Independence Day of India', '2026-08-15', '2026-08-15', 'single'),
('Gandhi Jayanti', 'Birth Anniversary of Mahatma Gandhi', '2026-10-02', '2026-10-02', 'single'),
('Diwali', 'Festival of Lights', '2026-10-24', '2026-10-24', 'single'),
('Christmas', 'Christmas Day', '2026-12-25', '2026-12-25', 'single')
ON DUPLICATE KEY UPDATE id=id;
