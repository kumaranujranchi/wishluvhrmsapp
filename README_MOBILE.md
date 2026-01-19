# Myworld HRMS - Mobile App

Complete mobile app setup for Myworld HRMS using Capacitor.

## 📱 Quick Start

This directory contains everything needed to build the Android mobile app.

### Prerequisites

- ✅ Node.js installed
- ✅ Android Studio installed
- ✅ Main HRMS web app at: `/Users/anujkumar/Desktop/hrms `

### First Time Setup

```bash
cd /Users/anujkumar/Desktop/hrms-mobile

# Verify dependencies are installed
npm install

# Sync latest web files from main HRMS
./sync-www.sh

# Sync to Android project
npx cap sync android

# Open in Android Studio
npx cap open android
```

### Build APK

In Android Studio:

1. **Build → Build Bundle(s) / APK(s) → Build APK(s)**
2. Wait for build to complete
3. APK location: `android/app/build/outputs/apk/debug/app-debug.apk`

Or via command line:

```bash
cd android
./gradlew assembleDebug
```

## 📂 Directory Structure

```
hrms-mobile/
├── android/                    # Native Android project
├── www/                        # Web assets (synced from main HRMS)
├── node_modules/              # Node dependencies
├── package.json               # Node configuration
├── capacitor.config.json      # Capacitor configuration
├── sync-www.sh               # Sync script
├── BUILD_GUIDE.md            # Detailed build instructions
├── PLAYSTORE_DEPLOYMENT.md   # Play Store guide
└── MOBILE_APP_README.md      # This file
```

## 🔄 Development Workflow

### When You Update Web App

1. Make changes in main HRMS directory: `/Users/anujkumar/Desktop/hrms `
2. Test web app on Hostinger
3. When ready to update mobile app:

```bash
cd /Users/anujkumar/Desktop/hrms-mobile

# Sync latest changes
./sync-www.sh

# Update Android project
npx cap sync android

# Rebuild in Android Studio
npx cap open android
```

## 📖 Documentation

- **[BUILD_GUIDE.md](BUILD_GUIDE.md)** - Complete build instructions
  - Debug APK build
  - Release APK/AAB build
  - Signing configuration
  - Testing guide

- **[PLAYSTORE_DEPLOYMENT.md](PLAYSTORE_DEPLOYMENT.md)** - Play Store publishing
  - Account setup
  - App listing
  - Submission process
  - Post-publication

- **[MOBILE_WORKFLOW.md](MOBILE_WORKFLOW.md)** - Development workflow
  - Git management
  - Sync process
  - Best practices

## 🚀 Common Commands

```bash
# Sync web files from main HRMS
./sync-www.sh

# Sync to Android
npx cap sync android

# Open in Android Studio
npx cap open android

# Build debug APK (command line)
cd android && ./gradlew assembleDebug

# Build release AAB (command line)
cd android && ./gradlew bundleRelease

# Install on device via ADB
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

## ⚙️ Configuration

### App Details

- **App ID**: `com.myworld.hrms`
- **App Name**: Myworld HRMS
- **Package**: `com.myworld.hrms`

### Permissions

- Internet access
- Network state
- Location (fine & coarse)
- Camera

### Capacitor Plugins

- `@capacitor/app`
- `@capacitor/camera`
- `@capacitor/geolocation`
- `@capacitor/splash-screen`
- `@capacitor/status-bar`

## 🔧 Troubleshooting

### Sync Issues

```bash
npx cap sync android --force
```

### Build Fails

```bash
cd android
./gradlew clean
./gradlew assembleDebug
```

### Web Assets Not Updating

```bash
./sync-www.sh
npx cap copy android
```

## 📝 Important Notes

> [!IMPORTANT]
> **Server Configuration**: Update `capacitor.config.json` with your production server URL before building release version.

> [!WARNING]
> **Don't edit files in `www/` directly**. Always edit in main HRMS directory and sync using `./sync-www.sh`.

## 🎯 Next Steps

1. ✅ Test app on Android emulator/device
2. ✅ Deploy backend to production server
3. ✅ Update API endpoints for production
4. ✅ Build release AAB
5. ✅ Submit to Google Play Store

## 📚 Resources

- **Capacitor Docs**: https://capacitorjs.com/docs
- **Android Studio**: https://developer.android.com/studio
- **Play Console**: https://play.google.com/console

---

**Ready to build!** 🚀 Follow the guides above to create your Android app.
