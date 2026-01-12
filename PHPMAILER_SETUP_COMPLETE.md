# ✅ PHPMailer Successfully Installed!

## 📁 Folder Structure (Current)

```
hrms/
├── vendor/
│   └── PHPMailer/
│       ├── PHPMailer.php ✅
│       ├── SMTP.php ✅
│       └── Exception.php ✅
├── config/
│   └── email.php (Email configuration)
└── forgot_password_otp.php (OTP reset page)
```

## 🎯 Next Steps - Email Configuration

### Step 1: Update Email Credentials

Open `config/email.php` and update these lines (around line 5-9):

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // Keep as is
define('SMTP_USERNAME', 'noreply@wishluvbuildcon.com'); // ← Your Hostinger email
define('SMTP_PASSWORD', 'your-actual-password-here'); // ← Your email password
define('SMTP_FROM_EMAIL', 'noreply@wishluvbuildcon.com'); // ← Same as username
define('SMTP_FROM_NAME', 'Myworld HRMS'); // ← Company name
```

### Step 2: Get Hostinger Email Settings

1. **Login to Hostinger** (screenshot aapne bheja hai)
2. Go to **Emails** section
3. Find or create email: `noreply@wishluvbuildcon.com`
4. Get these details:
   - **SMTP Server:** smtp.hostinger.com
   - **Port:** 465 (SSL)
   - **Username:** Full email address
   - **Password:** Your email password

### Step 3: Run Database Migration

Copy this SQL and run in your database (phpMyAdmin):

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

### Step 4: Test Password Reset

1. Go to: `your-domain.com/login.php`
2. Click **"Forgot password?"**
3. Enter:
   - Email: Employee ka registered email
   - Employee Code: e.g., WB001
4. Click **"Send OTP"**
5. Check email for 6-digit OTP
6. Enter OTP and reset password

## 🔧 Troubleshooting

### If OTP email not received:

1. **Check spam folder**
2. **Verify Hostinger email credentials** in `config/email.php`
3. **Test email account** - Try sending a test email from Hostinger panel
4. **Check PHP error logs** for any SMTP errors

### Common Issues:

**"SMTP connect() failed"**

- Wrong password or username
- Port blocked (try 587 instead of 465)

**"Could not authenticate"**

- Email password incorrect
- 2FA enabled (disable or use app password)

## 📝 Important Files

- ✅ `vendor/PHPMailer/` - PHPMailer library (installed)
- ✅ `config/email.php` - Email configuration (needs credentials)
- ✅ `forgot_password_otp.php` - OTP reset page
- ✅ `migration_otp.sql` - Database migration

## 🚀 Deployment Checklist

- [ ] Update email credentials in `config/email.php`
- [ ] Run `migration_otp.sql` in database
- [ ] Test OTP sending locally
- [ ] Deploy to server
- [ ] Test on live server
- [ ] Check email delivery

## 🔒 Security Notes

- ✅ `vendor/` folder added to `.gitignore`
- ✅ Email credentials NOT committed to Git
- ✅ OTP expires in 10 minutes
- ✅ One-time use only

---

**Need Help?**

- Check `EMAIL_SETUP.md` for detailed instructions
- Test with a simple PHPMailer script first
- Verify Hostinger email is active
