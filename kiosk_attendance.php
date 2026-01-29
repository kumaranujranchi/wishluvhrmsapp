<?php
session_start();
require_once 'config/db.php';

// Security: Only allow Kiosk User
if (!isset($_SESSION['user_id']) || $_SESSION['user_email'] !== 'kiosk@wishluvbuildcon.com') {
    header('Location: login.php');
    exit;
}

define('IS_KIOSK', true); // generic flag to hide sidebar/header
include 'includes/header.php';
?>

<style>
    /* Kiosk Specific Styles */
    body {
        overflow: hidden;
        /* Prevent scrolling during kiosk mode */
        background: #0f172a;
    }

    .main-sidebar,
    .main-header,
    .footer {
        display: none !important;
        /* Hide standard layout elements */
    }

    /* Override Main Content for Kiosk Centering */
    .main-content {
        margin-left: 0 !important;
        background: #0f172a !important;
        min-height: 100vh;
        width: 100vw !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        position: relative;
    }

    /* Ensure App Container doesn't constrain us */
    .app-container {
        width: 100vw !important;
        overflow-x: hidden;
    }

    /* Landing Screen */
    .kiosk-landing {
        text-align: center;
        color: white;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        padding: 4rem;
        border-radius: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 40px rgba(99, 102, 241, 0.2);
        max-width: 600px;
        width: 90%;
    }

    .kiosk-logo {
        width: 120px;
        margin-bottom: 2rem;
        filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
    }

    .capture-btn-lg {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        padding: 1.5rem 3rem;
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 2rem auto 0;
        transition: all 0.3s;
        box-shadow: 0 10px 30px -10px rgba(79, 70, 229, 0.6);
    }

    .capture-btn-lg:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.8);
    }

    .capture-btn-lg:active {
        transform: translateY(-2px);
    }

    /* Face Recognition Overlay */
    .kiosk-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #000;
        z-index: 9999;
        display: none;
        /* Hidden by default */
    }

    .kiosk-overlay.active {
        display: block;
    }

    .video-container {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    #kioskVideo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        /* Mirror */
    }

    #overlayCanvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        transform: scaleX(-1);
        /* Mirror to match video */
    }

    /* HUD Elements */
    .hud-header {
        position: absolute;
        top: 2rem;
        left: 0;
        width: 100%;
        text-align: center;
        z-index: 100;
        pointer-events: none;
    }

    .hud-status {
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: inline-block;
        font-family: 'Courier New', monospace;
        text-transform: uppercase;
        letter-spacing: 2px;
        backdrop-filter: blur(5px);
    }

    .face-rect {
        position: absolute;
        border: 2px solid rgba(99, 102, 241, 0.8);
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        border-radius: 20px;
        transition: all 0.2s ease-out;
        pointer-events: none;
    }

    .face-rect::after {
        content: '';
        position: absolute;
        top: -10px;
        left: -10px;
        right: -10px;
        bottom: -10px;
        border: 2px solid transparent;
        border-top-color: #6366f1;
        border-bottom-color: #6366f1;
        animation: scan-vertical 2s linear infinite;
    }

    @keyframes scan-vertical {
        0% {
            transform: translateY(0);
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            transform: translateY(100vh);
            opacity: 0;
        }
    }

    .scanning-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(to right, rgba(99, 102, 241, 0), #6366f1, rgba(99, 102, 241, 0));
        z-index: 150;
        display: none;
        box-shadow: 0 0 15px #6366f1;
    }

    .scanning-line.active {
        display: block;
        animation: scan-scoped 2s linear infinite;
    }

    .countdown-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 8rem;
        font-weight: 900;
        color: white;
        text-shadow: 0 0 20px rgba(99, 102, 241, 0.8);
        z-index: 2100;
        pointer-events: none;
        display: none;
    }

    .countdown-text.visible {
        display: block;
    }

    @keyframes scan-scoped {
        0% {
            transform: translateY(0);
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            transform: translateY(380px);
            opacity: 0;
        }
    }

    .recognition-result {
        position: absolute;
        top: 20%;
        /* Move higher up */
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.98);
        padding: 2rem 3rem;
        border-radius: 2rem;
        text-align: center;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
        z-index: 2000;
        /* Higher than scan button */
        width: 85%;
        max-width: 550px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transform: translate(-50%, -20px);
    }

    .recognition-result.visible {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, 0);
    }

    .result-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
        border: 4px solid #10b981;
    }

    .result-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }

    .result-time {
        font-size: 1rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .close-kiosk-btn {
        position: absolute;
        top: 2rem;
        right: 2rem;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: none;
        padding: 0.8rem;
        border-radius: 50%;
        cursor: pointer;
        z-index: 101;
        transition: background 0.3s;
    }

    .close-kiosk-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .kiosk-landing {
            padding: 2rem;
            width: 90%;
            margin: 0 auto;
            /* Ensure centering works even if flex fails */
        }

        /* Force override header.php mobile styles */
        .main-content {
            display: flex !important;
            flex-direction: column;
            justify-content: center !important;
            min-height: 100vh !important;
        }

        .kiosk-logo {
            width: 100px;
        }

        .capture-btn-lg {
            padding: 1.2rem 2rem;
            font-size: 1.2rem;
            width: 100%;
            justify-content: center;
        }

        .close-kiosk-btn {
            top: 1rem;
            right: 1rem;
            padding: 0.6rem;
        }

        .hud-header {
            top: 4rem;
            /* Below close button */
        }

        .hud-status {
            font-size: 0.9rem;
            padding: 0.4rem 1rem;
        }

        #faceGuide {
            width: 70vw !important;
            height: 90vw !important;
            max-width: 300px;
            max-height: 400px;
            border-radius: 35% !important;
        }

        .countdown-text {
            font-size: 5rem;
        }

        .recognition-result {
            width: 90%;
            padding: 1.5rem;
        }

        .result-name {
            font-size: 1.2rem;
        }
    }
</style>

<!-- FACE API JS -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/dist/face-api.js"></script>

<div class="kiosk-landing">
    <img src="assets/logo.png" alt="Logo" class="kiosk-logo">
    <h1>HRMS Attendance Kiosk</h1>
    <p style="color: #94a3b8; margin-top: 1rem;">Touch the button below to mark your attendance using face verification.
    </p>

    <button class="capture-btn-lg" onclick="startKioskMode()" id="startKioskBtn">
        <i data-lucide="scan-face" style="width: 32px; height: 32px;"></i>
        Capture Attendance
    </button>
</div>

<!-- KIOSK UI OVERLAY -->
<div class="kiosk-overlay" id="kioskOverlay">
    <div class="video-container">
        <video id="kioskVideo" autoplay playsinline muted></video>
        <!-- Scanning line moved inside faceGuide -->
        <!-- Overlay Canvas Removed -->

        <!-- Visual Guide Frame -->
        <div class="face-guide-overlay" id="faceGuide"
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; height: 380px; border: 2px dashed rgba(255,255,255,0.3); border-radius: 40%; pointer-events: none; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);">
            <div class="scanning-line" id="scanningLine"></div>
            <div class="countdown-text" id="countdownOverlay">3</div>
            <div
                style="position: absolute; bottom: -30px; width: 100%; text-align: center; color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;">
                Align Face Here</div>
        </div>

        <div class="hud-header">
            <span class="hud-status" id="hudStatus">System Active • Scanning...</span>
        </div>

        <button class="close-kiosk-btn" onclick="stopKioskMode()">
            <i data-lucide="x" style="width: 24px; height: 24px;"></i>
        </button>

        <div class="recognition-result" id="resultCard">
            <img src="" alt="User" class="result-avatar" id="resultAvatar">
            <h3 class="result-name" id="resultName"></h3>
            <p class="result-time" id="resultTime"></p>
        </div>
    </div>
</div>

<script src="assets/js/kiosk_core.js"></script>

<?php include 'includes/footer.php'; ?>