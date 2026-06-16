import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.registerServiceWorker();
        this.updateOnlineStatus();
        window.addEventListener('online', () => this.updateOnlineStatus());
        window.addEventListener('offline', () => this.updateOnlineStatus());
    }

    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return;
        try {
            await navigator.serviceWorker.register('/sw.js');
        } catch (e) {
            console.warn('SW registration failed', e);
        }
    }

    updateOnlineStatus() {
        const banner = document.getElementById('offline-banner');
        if (banner) {
            banner.classList.toggle('visible', !navigator.onLine);
        }
    }
}
