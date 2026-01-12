-- Create policies table for dynamic policy management
CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'file-text',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default policies (if they don't exist)
INSERT INTO policies (title, slug, content, icon, display_order) VALUES
('HR Policy', 'policy_hr', '<h2>HR Policy</h2><p>Add your HR policy content here...</p>', 'briefcase', 1),
('Leave Policy', 'policy_leave', '<h2>Leave Policy</h2><p>Add your leave policy content here...</p>', 'coffee', 2),
('Dress Code', 'policy_dress', '<h2>Dress Code Policy</h2><p>Add your dress code policy content here...</p>', 'shirt', 3)
ON DUPLICATE KEY UPDATE id=id;
