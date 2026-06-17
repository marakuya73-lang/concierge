import { Controller } from '@hotwired/stimulus';

const LAST_SEEN_KEY = 'domoAdminLastSeenExtra';
const POLL_INTERVAL_MS = 10000;
const SEEN_IDS_KEY = 'domoAdminSeenExtraIds';
const POLL_OVERLAP_SECONDS = 15;

export default class extends Controller {
    static targets = ['banner', 'prompt', 'promptText', 'enableBtn', 'status', 'testBtn'];

    connect() {
        this.seenIds = this.loadSeenIds();
        this.lastSeen = this.loadLastSeen();
        this.pollTimer = null;
        this.audioContext = null;
        this.pushSubscribed = false;

        this.setupNotifications();
        this.refreshStatus();
        this.startPolling();

        document.addEventListener('visibilitychange', this.onVisibilityChange);
        window.addEventListener('online', this.onOnline);
    }

    disconnect() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
        document.removeEventListener('visibilitychange', this.onVisibilityChange);
        window.removeEventListener('online', this.onOnline);
    }

    onVisibilityChange = () => {
        if (!document.hidden) {
            this.poll();
        }
    };

    onOnline = () => {
        this.poll();
        this.subscribeToPush();
    };

    async setupNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            this.setStatus('Notificações push não suportadas neste navegador.');
            return;
        }

        try {
            await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });
        } catch (e) {
            console.warn('Admin SW registration failed', e);
            this.setStatus('Erro ao registar service worker.');
            return;
        }

        const permission = Notification.permission;

        if (permission === 'granted') {
            await this.subscribeToPush();
            this.hidePrompt();
            return;
        }

        if (permission === 'default' && this.hasPromptTarget) {
            this.promptTarget.hidden = false;
        } else if (permission === 'denied') {
            this.setStatus('Notificações bloqueadas. Ative nas definições do telemóvel/navegador.');
        }
    }

    async enableNotifications(event) {
        event?.preventDefault();

        if (!('Notification' in window)) {
            alert('Notificações não são suportadas neste navegador.');
            return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            if (this.hasPromptTextTarget) {
                this.promptTextTarget.textContent = 'Permissão negada. Ative nas configurações do navegador.';
            }
            return;
        }

        const subscribed = await this.subscribeToPush();
        if (subscribed) {
            this.hidePrompt();
            this.playAlert();
        }
        await this.refreshStatus();
    }

    async testNotification(event) {
        event?.preventDefault();

        try {
            const response = await fetch('/admin/api/notifications/test', { method: 'POST' });
            const data = await response.json();
            if (!response.ok) {
                alert(data.error || 'Teste falhou.');
                return;
            }
            alert(`Notificação de teste enviada (${data.sent} dispositivo(s)).`);
        } catch {
            alert('Não foi possível enviar o teste.');
        }
    }

    hidePrompt() {
        if (this.hasPromptTarget) {
            this.promptTarget.hidden = true;
        }
    }

    async subscribeToPush() {
        try {
            const configResponse = await fetch('/admin/api/notifications/vapid-key');
            const config = await configResponse.json();
            if (!config.configured || !config.publicKey) {
                this.setStatus('Servidor: chaves VAPID em falta no .env');
                return false;
            }

            const registration = await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(config.publicKey),
                });
            }

            const payload = this.serializeSubscription(subscription);
            const subscribeResponse = await fetch('/admin/api/notifications/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (!subscribeResponse.ok) {
                const error = await subscribeResponse.json().catch(() => ({}));
                this.setStatus(error.error || 'Erro ao registar subscrição push.');
                return false;
            }

            this.pushSubscribed = true;
            await this.refreshStatus();
            return true;
        } catch (e) {
            console.warn('Push subscription failed', e);
            this.setStatus('Erro ao activar push. Tente recarregar a página.');
            return false;
        }
    }

    serializeSubscription(subscription) {
        const json = subscription.toJSON();
        let contentEncoding = 'aesgcm';

        if ('supportedContentEncodings' in PushManager && PushManager.supportedContentEncodings.length > 0) {
            contentEncoding = PushManager.supportedContentEncodings.includes('aes128gcm')
                ? 'aes128gcm'
                : PushManager.supportedContentEncodings[0];
        }

        return {
            endpoint: json.endpoint,
            keys: json.keys,
            contentEncoding,
        };
    }

    async refreshStatus() {
        try {
            const response = await fetch('/admin/api/notifications/status');
            if (!response.ok) return;

            const data = await response.json();
            const parts = [];

            if (!data.pushConfigured) {
                parts.push('VAPID não configurado no servidor');
            } else if (data.subscriptionCount === 0) {
                parts.push('Push: aguardando subscrição neste dispositivo');
            } else {
                parts.push(`Push activo (${data.subscriptionCount} dispositivo(s))`);
            }

            if (Notification.permission === 'granted') {
                parts.push('permissão OK');
            }

            this.setStatus(parts.join(' · '));

            if (this.hasTestBtnTarget) {
                this.testBtnTarget.hidden = !data.pushConfigured || data.subscriptionCount === 0;
            }

            if (data.pushConfigured && data.subscriptionCount > 0 && Notification.permission === 'granted') {
                this.hidePrompt();
            } else if (Notification.permission === 'default' && this.hasPromptTarget) {
                this.promptTarget.hidden = false;
            }
        } catch {
            this.setStatus('Não foi possível verificar estado das notificações.');
        }
    }

    setStatus(message) {
        if (this.hasStatusTarget && message) {
            this.statusTarget.textContent = message;
        }
    }

    startPolling() {
        this.poll();
        this.pollTimer = setInterval(() => this.poll(), POLL_INTERVAL_MS);
    }

    async poll() {
        try {
            const since = Math.max(0, this.lastSeen - POLL_OVERLAP_SECONDS);
            const response = await fetch(`/admin/api/notifications/recent?since=${since}`);
            if (!response.ok) return;

            const data = await response.json();

            const newRequests = (data.requests || []).filter((req) => !this.seenIds.has(req.id));
            for (const req of newRequests) {
                this.handleNewRequest(req);
            }

            if (data.serverTime) {
                this.lastSeen = data.serverTime;
                this.saveLastSeen();
            }
        } catch {
            // offline or session expired
        }
    }

    handleNewRequest(req) {
        this.seenIds.add(req.id);
        this.saveSeenIds();

        this.showBanner(req);
        this.vibrate();
        this.playAlert();

        if (Notification.permission === 'granted') {
            this.showLocalNotification(req);
        }
    }

    showBanner(req) {
        if (!this.hasBannerTarget) return;

        this.bannerTarget.hidden = false;
        this.bannerTarget.innerHTML = `
            <div class="admin-alert-banner-inner">
                <strong>Nova solicitação de extra</strong>
                <span>${req.guestName} · ${req.extraName} ×${req.quantity} · ${req.totalFormatted}</span>
            </div>
            <a href="${req.bookingUrl}" class="admin-alert-banner-link">Ver reserva →</a>
            <button type="button" class="admin-alert-banner-dismiss" data-action="click->admin-notifications#dismissBanner" aria-label="Fechar">×</button>
        `;
    }

    dismissBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.hidden = true;
            this.bannerTarget.innerHTML = '';
        }
    }

    showLocalNotification(req) {
        const title = `Nova solicitação — ${req.extraName}`;
        const body = `${req.guestName} · ×${req.quantity} · ${req.totalFormatted}`;
        const options = {
            body,
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            vibrate: [200, 100, 200, 100, 400],
            tag: `extra-request-${req.id}`,
            renotify: true,
            requireInteraction: true,
            data: { url: req.bookingUrl },
        };

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready
                .then((reg) => reg.showNotification(title, options))
                .catch(() => {
                    try { new Notification(title, options); } catch { /* unsupported */ }
                });
        } else {
            try { new Notification(title, options); } catch { /* unsupported */ }
        }
    }

    vibrate() {
        if ('vibrate' in navigator) {
            navigator.vibrate([200, 100, 200, 100, 400]);
        }
    }

    playAlert() {
        try {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            const ctx = this.audioContext;
            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            const playTone = (freq, start, duration) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.25, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(start);
                osc.stop(start + duration);
            };

            const now = ctx.currentTime;
            playTone(880, now, 0.15);
            playTone(1108, now + 0.18, 0.2);
        } catch {
            // audio not available
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    loadLastSeen() {
        const stored = sessionStorage.getItem(LAST_SEEN_KEY);
        return stored ? parseInt(stored, 10) : Math.floor(Date.now() / 1000) - 60;
    }

    saveLastSeen() {
        sessionStorage.setItem(LAST_SEEN_KEY, String(this.lastSeen));
    }

    loadSeenIds() {
        try {
            const raw = sessionStorage.getItem(SEEN_IDS_KEY);
            return new Set(raw ? JSON.parse(raw) : []);
        } catch {
            return new Set();
        }
    }

    saveSeenIds() {
        sessionStorage.setItem(SEEN_IDS_KEY, JSON.stringify([...this.seenIds]));
    }
}
