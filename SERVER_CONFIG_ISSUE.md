# Mobile App Server Configuration Issue

## Problem

The mobile app is showing PHP code instead of the rendered website because the server URL is not configured correctly.

## Current Configuration

```json
{
  "server": {
    "hostname": "app.myworld-hrms.com"
  }
}
```

This domain doesn't exist, so the app is loading PHP files directly without processing them.

## Solution Required

**We need your production server URL where the HRMS web app is deployed.**

### Examples:

- `https://hrms.yourdomain.com`
- `https://yourdomain.com/hrms`
- `https://your-hostinger-domain.com`

### Steps After URL is Provided:

1. Update `capacitor.config.json` with correct URL
2. Sync changes: `npx cap sync android`
3. Rebuild APK: `./gradlew assembleDebug`
4. Install new APK on phone

## Temporary Solution (For Testing)

If you want to test locally first:

1. Start PHP server on your Mac:

```bash
cd "/Users/anujkumar/Desktop/hrms "
php -S 0.0.0.0:8000
```

2. Find your Mac's IP address:

```bash
ipconfig getifaddr en0
```

3. Update config with: `http://YOUR_MAC_IP:8000`

But for production, you need the actual Hostinger URL.

---

**Please provide your production server URL to fix this issue.**
