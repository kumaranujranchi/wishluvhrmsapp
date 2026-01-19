-- Create System Settings Table for App Updates
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Initialize default app settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('latest_apk_url', ''),
('latest_apk_version', '1.0.0'),
('latest_apk_notes', 'Initial release');
