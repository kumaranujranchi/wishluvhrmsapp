# Mobile App Development

The mobile app files have been moved to a separate directory to keep this codebase clean for Hostinger deployment.

## Mobile App Location

📱 **Mobile App Directory**: `/Users/anujkumar/Desktop/hrms-mobile`

All mobile app related files (Capacitor, Android project, build files) are now in the separate directory.

## Development Workflow

### For Web App (This Directory)

```bash
# Normal development - deploy to Hostinger
cd "/Users/anujkumar/Desktop/hrms "
# Edit PHP files
git add .
git commit -m "your message"
git push origin main
```

### For Mobile App

```bash
# Switch to mobile app directory
cd /Users/anujkumar/Desktop/hrms-mobile

# Sync latest changes from web app
./sync-www.sh

# Sync to Android
npx cap sync android

# Open in Android Studio
npx cap open android

# Build APK
```

## Documentation

All mobile app documentation is in the `hrms-mobile` directory:

- `BUILD_GUIDE.md` - How to build APK/AAB
- `PLAYSTORE_DEPLOYMENT.md` - How to publish to Play Store
- `MOBILE_APP_README.md` - Quick start guide
- `MOBILE_WORKFLOW.md` - Detailed workflow

## Benefits of Separation

✅ Clean web app codebase for Hostinger
✅ No mobile files in Git repository
✅ Easier to manage both projects
✅ No deployment conflicts

---

**Note**: This directory contains only the web application. For mobile app development, use the `hrms-mobile` directory.
