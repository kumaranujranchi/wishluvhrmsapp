// PWA Install Prompt Handler
let deferredPrompt;
const installButton = document.getElementById('pwa-install-btn');

// Listen for beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('[PWA] Install prompt available');
    
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    
    // Show install button if it exists
    if (installButton) {
        installButton.style.display = 'block';
    }
});

// Handle install button click
if (installButton) {
    installButton.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }
        
        // Show the install prompt
        deferredPrompt.prompt();
        
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`[PWA] User response: ${outcome}`);
        
        // Clear the deferredPrompt
        deferredPrompt = null;
        
        // Hide the install button
        installButton.style.display = 'none';
    });
}

// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then((registration) => {
                console.log('[PWA] Service Worker registered:', registration.scope);
                
                // Check for updates periodically
                setInterval(() => {
                    registration.update();
                }, 60000); // Check every minute
            })
            .catch((error) => {
                console.log('[PWA] Service Worker registration failed:', error);
            });
    });
    
    // Listen for service worker updates
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        console.log('[PWA] New service worker activated');
        // Optionally show a notification to user
        if (confirm('New version available! Reload to update?')) {
            window.location.reload();
        }
    });
}

// Check if app is installed
window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed successfully');
    
    // Hide install button
    if (installButton) {
        installButton.style.display = 'none';
    }
    
    // Track installation (optional analytics)
    // gtag('event', 'pwa_install');
});

// Detect if running as PWA
function isPWA() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true;
}

// Add PWA class to body if running as app
if (isPWA()) {
    document.body.classList.add('pwa-mode');
    console.log('[PWA] Running as installed app');
}

// Handle online/offline status
window.addEventListener('online', () => {
    console.log('[PWA] Back online');
    document.body.classList.remove('offline-mode');
});

window.addEventListener('offline', () => {
    console.log('[PWA] Gone offline');
    document.body.classList.add('offline-mode');
});
