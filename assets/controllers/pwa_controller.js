import { Controller } from '@hotwired/stimulus';
import { canUseCachedStay, clearStayCache } from '../utils/stay_access.js';

const PENDING_EXTRAS_KEY = 'domoPendingExtraRequests';

export default class extends Controller {
    connect() {
        this.registerServiceWorker();
        this.updateOnlineStatus();
        this.redirectToCachedStay();
        this.syncPendingRequests();
        window.addEventListener('online', () => {
            this.updateOnlineStatus();
            this.syncPendingRequests();
        });
        window.addEventListener('offline', () => this.updateOnlineStatus());
    }

    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return;

        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                updateViaCache: 'none',
            });

            registration.addEventListener('updatefound', () => {
                const worker = registration.installing;
                if (!worker) return;

                worker.addEventListener('statechange', () => {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showUpdateBanner(worker);
                    }
                });
            });
        } catch (e) {
            console.warn('SW registration failed', e);
        }
    }

    showUpdateBanner(worker) {
        const banner = document.getElementById('pwa-update-banner');
        const button = document.getElementById('pwa-update-btn');
        if (!banner || !button) return;

        banner.hidden = false;

        button.onclick = () => {
            worker.postMessage({ type: 'SKIP_WAITING' });
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                window.location.reload();
            }, { once: true });
        };
    }

    redirectToCachedStay() {
        if (navigator.onLine || window.location.pathname !== '/') return;

        const code = localStorage.getItem('accessCode');
        if (code && canUseCachedStay(code)) {
            window.location.href = '/stay/' + code;
            return;
        }

        clearStayCache();
    }

    updateOnlineStatus() {
        const banner = document.getElementById('offline-banner');
        if (banner) {
            banner.classList.toggle('visible', !navigator.onLine);
        }
    }

    async syncPendingRequests() {
        if (!navigator.onLine) return;

        const raw = localStorage.getItem(PENDING_EXTRAS_KEY);
        if (!raw) return;

        let queue;
        try {
            queue = JSON.parse(raw);
        } catch {
            localStorage.removeItem(PENDING_EXTRAS_KEY);
            return;
        }

        if (!Array.isArray(queue) || queue.length === 0) return;

        const remaining = [];
        for (const item of queue) {
            try {
                const response = await fetch('/api/concierge/extras/request', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: item.code,
                        extraId: item.extraId,
                        quantity: item.quantity,
                        notes: item.notes,
                    }),
                });
                if (!response.ok) remaining.push(item);
            } catch {
                remaining.push(item);
            }
        }

        if (remaining.length) {
            localStorage.setItem(PENDING_EXTRAS_KEY, JSON.stringify(remaining));
        } else {
            localStorage.removeItem(PENDING_EXTRAS_KEY);
        }
    }
}
