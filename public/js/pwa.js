/**
 * PWA Installation Manager
 * Handles service worker registration, push notifications, and PWA install prompts
 */
class PwaManager {
    constructor() {
        this.hasServiceWorker = 'serviceWorker' in navigator;
        this.hasNotificationSupport = 'Notification' in window;
        this.deferredPrompt = null;
        this.isInstalled = false;
        
        this.init();
    }

    async init() {
        console.log('[PWA] Initializing PWA Manager');
        
        // Register service worker
        if (this.hasServiceWorker) {
            this.registerServiceWorker();
        }

        // Check if already installed
        this.checkIfInstalled();

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA] Install prompt triggered');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallPrompt();
        });

        // Listen for app installed
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed');
            this.isInstalled = true;
            this.hideInstallPrompt();
            this.trackInstallation();
            this.showInstallationMessage();
        });
    }

    /**
     * Register Service Worker
     */
    async registerServiceWorker() {
        try {
            console.log('[PWA] Registering service worker...');
            
            const registration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
            });

            console.log('[PWA] Service Worker registered successfully', registration);

            // Check for updates immediately and periodically
            const checkForUpdates = () => {
                registration.update().then(() => {
                    console.log('[PWA] Service Worker update checked');
                }).catch(err => {
                    console.error('[PWA] Service Worker update failed:', err);
                });
            };

            // Check immediately
            checkForUpdates();

            // Check every 5 minutes
            setInterval(checkForUpdates, 300000);

            // Handle controller change (new service worker activated)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[PWA] Service Worker controller changed - reloading page');
                window.location.reload();
            });

            // Handle service worker updates
            registration.addEventListener('updatefound', () => {
                console.log('[PWA] Service Worker update found');
                const newWorker = registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[PWA] New service worker installed, will activate on next page load');
                        // Optionally show update notification to user
                        this.showUpdateNotification();
                    }
                });
            });

            return registration;
        } catch (error) {
            console.error('[PWA] Service Worker registration failed:', error);
        }
    }

    /**
     * Show install prompt
     */
    showInstallPrompt() {
        const installButton = document.getElementById('pwa-install-button');
        const installBanner = document.getElementById('pwa-install-banner');

        if (installButton) {
            installButton.style.display = 'flex';
            installButton.addEventListener('click', () => {
                this.installApp();
            });
        }

        if (installBanner) {
            installBanner.style.display = 'flex';
        }
    }

    /**
     * Hide install prompt
     */
    hideInstallPrompt() {
        const installButton = document.getElementById('pwa-install-button');
        const installBanner = document.getElementById('pwa-install-banner');

        if (installButton) {
            installButton.style.display = 'none';
        }

        if (installBanner) {
            installBanner.style.display = 'none';
        }
    }

    /**
     * Trigger app installation
     */
    async installApp() {
        if (!this.deferredPrompt) {
            console.log('[PWA] Install prompt not available');
            return;
        }

        try {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log(`[PWA] User response: ${outcome}`);

            if (outcome === 'accepted') {
                this.trackInstallation('prompt');
            }

            this.deferredPrompt = null;
        } catch (error) {
            console.error('[PWA] Installation failed:', error);
        }
    }

    /**
     * Check if app is installed
     */
    checkIfInstalled() {
        // Check display mode
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('[PWA] App is running in standalone mode');
            this.isInstalled = true;
            this.hideInstallPrompt();
            return;
        }

        // Check if running as PWA (fullscreen mode)
        if (navigator.standalone === true) {
            console.log('[PWA] App is running as PWA (iOS)');
            this.isInstalled = true;
            this.hideInstallPrompt();
            return;
        }

        // Fetch status from server
        this.getInstallationStatus();
    }

    /**
     * Get installation status from server
     */
    async getInstallationStatus() {
        try {
            const response = await fetch('/api/pwa/status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.isInstalled = data.installed;
                
                if (this.isInstalled) {
                    this.hideInstallPrompt();
                    console.log('[PWA] App is installed on this device');
                }
            }
        } catch (error) {
            console.error('[PWA] Failed to get installation status:', error);
        }
    }

    /**
     * Track installation on server
     */
    async trackInstallation(method = 'system') {
        try {
            const response = await fetch('/api/pwa/install', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    platform: this.detectPlatform()
                })
            });

            if (response.ok) {
                console.log('[PWA] Installation tracked on server');
            }
        } catch (error) {
            console.error('[PWA] Failed to track installation:', error);
        }
    }

    /**
     * Show installation confirmation message
     */
    showInstallationMessage() {
        const message = document.createElement('div');
        message.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 z-50 animate-bounce';
        message.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>App installed successfully!</span>
        `;
        
        document.body.appendChild(message);

        setTimeout(() => {
            message.remove();
        }, 4000);
    }

    /**
     * Request notification permission
     */
    async requestNotificationPermission() {
        if (!this.hasNotificationSupport) {
            console.warn('[PWA] Notifications not supported');
            return false;
        }

        try {
            const permission = await Notification.requestPermission();
            console.log('[PWA] Notification permission:', permission);
            
            if (permission === 'granted') {
                await this.subscribeToPush();
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('[PWA] Failed to request notification permission:', error);
            return false;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPush() {
        if (!this.hasServiceWorker) {
            console.warn('[PWA] Service Worker not available for push');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(
                    this.getPushPublicKey()
                )
            });

            console.log('[PWA] Push subscription successful:', subscription);

            // Send subscription to server
            await this.sendPushSubscriptionToServer(subscription);
        } catch (error) {
            console.error('[PWA] Failed to subscribe to push notifications:', error);
        }
    }

    /**
     * Send push subscription to server
     */
    async sendPushSubscriptionToServer(subscription) {
        try {
            const response = await fetch('/api/pwa/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: this.arrayBufferToBase64(
                            subscription.getKey('p256dh')
                        ),
                        auth: this.arrayBufferToBase64(
                            subscription.getKey('auth')
                        )
                    }
                })
            });

            if (response.ok) {
                console.log('[PWA] Push subscription sent to server');
            }
        } catch (error) {
            console.error('[PWA] Failed to send push subscription to server:', error);
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribeFromPush() {
        if (!this.hasServiceWorker) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();

                // Notify server
                await fetch('/api/pwa/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken()
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                });

                console.log('[PWA] Unsubscribed from push notifications');
            }
        } catch (error) {
            console.error('[PWA] Failed to unsubscribe from push:', error);
        }
    }

    /**
     * Request permission to use features
     */
    async requestPermission(feature) {
        switch (feature) {
            case 'notifications':
                return await this.requestNotificationPermission();
            default:
                return false;
        }
    }

    /**
     * Helper: Get CSRF token
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Helper: Detect platform
     */
    detectPlatform() {
        const ua = navigator.userAgent.toLowerCase();
        
        if (/iphone|ipod|ipad/.test(ua)) return 'ios';
        if (/android/.test(ua)) return 'android';
        if (/windows/.test(ua)) return 'windows';
        if (/mac/.test(ua)) return 'mac';
        if (/linux/.test(ua)) return 'linux';
        
        return 'unknown';
    }

    /**
     * Helper: Get push public key
     */
    getPushPublicKey() {
        return document.querySelector('meta[name="push-public-key"]')?.content || '';
    }

    /**
     * Helper: Convert URL base64 to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Helper: Convert ArrayBuffer to base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        
        return window.btoa(binary);
    }
}

// Initialize PWA Manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pwaManager = new PwaManager();
    });
} else {
    window.pwaManager = new PwaManager();
}
