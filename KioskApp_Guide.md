# Wishluv Kiosk App Guide

I have created a dedicated Android App for the Kiosk feature in the `KioskApp` folder. This app is built with **React Native (Expo)** to ensure stability and high performance.

## Prerequisites

1.  **Node.js** installed on your computer.
2.  **Expo Go** app installed on your Android tablet (from Play Store).

## How to Run locally (Development)

1.  Open your terminal and navigate to the project folder:
    ```bash
    cd KioskApp
    ```
2.  Start the Expo server:
    ```bash
    npx expo start
    ```
3.  Open the **Expo Go** app on your tablet and scan the QR code displayed in your terminal.
4.  The app will load and connect to your local server.

## How to Build the APK (Production)

To create a standalone APK that you can install directly on the tablet without needing Expo Go:

1.  **Install EAS CLI**:
    ```bash
    npm install -g eas-cli
    ```
2.  **Log in to Expo**:
    ```bash
    eas login
    ```
3.  **Build the APK**:
    ```bash
    eas build -p android --profile preview
    ```
    _Note: This will give you a download link for the APK at the end._

## App Features

- **Auto-Detection**: No need to tap buttons.
- **Countdown**: 3-second countdown once a face is stable.
- **Persistence**: Built to stay active and responsive.
