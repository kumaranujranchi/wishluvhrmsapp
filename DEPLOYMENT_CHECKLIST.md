# SMTP Email Fix - Deployment Checklist

## 📋 Files to Upload to Server

Make sure these files are uploaded to your live server:

### 1. Core Files (Already in Git)

- ✅ `config/email.php` - Email configuration
- ✅ `forgot_password_otp.php` - OTP reset page
- ✅ `vendor/PHPMailer/` - PHPMailer library (entire folder)
- ✅ `smtp_test.php` - SMTP test file
- ✅ `test_email.php` - Configuration test

### 2. Database Migration

Run this SQL in your live database:

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

## 🚀 Deployment Steps

### Step 1: Push to GitHub (Already Done ✅)

```bash
git push origin main
```

### Step 2: Deploy to Hostinger

**Option A: Auto Deployment (If configured)**

- Hostinger will auto-pull from GitHub
- Check deployment status in Hostinger panel

**Option B: Manual Upload**

1. Login to Hostinger File Manager
2. Upload these folders/files:
   - `vendor/PHPMailer/` (entire folder)
   - `config/email.php`
   - `forgot_password_otp.php`
   - `smtp_test.php`
   - `test_email.php`

### Step 3: Run Database Migration

1. Go to phpMyAdmin in Hostinger
2. Select your database
3. Run the SQL from `migration_otp.sql`

### Step 4: Test SMTP Connection

**Test URL:**

```
https://hrms.wishluvbuildcon.com/smtp_test.php
```

This will show:

- SMTP connection status
- Authentication result
- Exact error if any

### Step 5: Test Password Reset

**Test URL:**

```
https://hrms.wishluvbuildcon.com/forgot_password_otp.php
```

## 🔧 If SMTP Still Fails

### Check Hostinger Settings:

1. **Email Account Active?**

   - Hostinger → Emails
   - Check `noreply@wishluvbuildcon.com` is active

2. **SMTP Enabled?**

   - Some hosts disable SMTP by default
   - Contact Hostinger support if needed

3. **Server Firewall?**
   - Port 587 might be blocked
   - Try port 465 (SSL) instead

### Alternative Ports to Try:

**Port 587 (Current):**

```php
define('SMTP_PORT', 587);
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
```

**Port 465 (Alternative):**

```php
define('SMTP_PORT', 465);
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
```

## 📞 Hostinger Support

If SMTP still doesn't work:

1. Contact Hostinger support
2. Ask: "Is SMTP enabled for my hosting plan?"
3. Confirm: Port 587 or 465 is open
4. Verify: Email account can send via SMTP

## ✅ Success Indicators

When working, you'll see:

- ✅ `smtp_test.php` shows "Email sent successfully!"
- ✅ OTP email received in inbox
- ✅ Password reset flow completes

## 🐛 Debug Output

`smtp_test.php` will show detailed SMTP conversation:

```
220 smtp.hostinger.com ESMTP
EHLO ...
250 AUTH LOGIN
AUTH LOGIN
334 ...
235 Authentication successful
MAIL FROM: ...
250 OK
```

---

**Current Status:**

- Code: ✅ Ready
- Database: ⏳ Need to run migration
- Deployment: ⏳ Need to upload files
- Testing: ⏳ Pending deployment
