# Email Setup Instructions for HRMS

## 📧 OTP-Based Password Reset Setup

### Step 1: Install PHPMailer

Run this command in your project root directory:

```bash
composer require phpmailer/phpmailer
```

**OR** manually download PHPMailer:

1. Download from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract and place in `vendor/PHPMailer/` folder

### Step 2: Configure Hostinger Email

Edit `config/email.php` and update these settings:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // or 587 for TLS
define('SMTP_USERNAME', 'noreply@yourcompany.com'); // Your Hostinger email
define('SMTP_PASSWORD', 'your-email-password'); // Your email password
define('SMTP_FROM_EMAIL', 'noreply@yourcompany.com');
define('SMTP_FROM_NAME', 'Myworld HRMS');
```

### Step 3: Get Hostinger Email Credentials

1. Login to Hostinger Control Panel
2. Go to **Emails** section
3. Find your email account (or create one like `noreply@yourcompany.com`)
4. Get SMTP settings:
   - **SMTP Server:** smtp.hostinger.com
   - **Port:** 465 (SSL) or 587 (TLS)
   - **Username:** Your full email address
   - **Password:** Your email password

### Step 4: Run Database Migration

Run this SQL in your database:

```sql
CREATE TABLE IF NOT EXISTS password_reset_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_email_otp (email, otp),
    INDEX idx_expires (expires_at)
);
```

### Step 5: Test Email Functionality

1. Go to login page
2. Click "Forgot password?"
3. Enter email and employee code
4. Check if OTP email is received

## 🔒 Security Features

✅ **OTP Expiry:** 10 minutes validity
✅ **One-time Use:** OTP marked as used after successful reset
✅ **Dual Verification:** Email + Employee Code required
✅ **Secure Storage:** OTPs stored in database with expiry
✅ **Email Verification:** OTP sent only to registered email

## 📝 Files Created

- `config/email.php` - Email configuration and functions
- `forgot_password_otp.php` - OTP-based password reset page
- `migration_otp.sql` - Database migration for OTP table

## ⚠️ Important Notes

1. **Never commit** email credentials to Git
2. Use environment variables for production
3. Test email sending on localhost first
4. Ensure Hostinger email is active and verified
5. Check spam folder if OTP not received

## 🚀 Alternative: Use .env File (Recommended for Production)

Create `.env` file:

```
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_USERNAME=noreply@yourcompany.com
SMTP_PASSWORD=your-password
```

Then update `config/email.php` to read from `.env`

## 📞 Support

If emails are not sending:

1. Check Hostinger email credentials
2. Verify SMTP settings
3. Check server error logs
4. Test with a simple PHPMailer script first
