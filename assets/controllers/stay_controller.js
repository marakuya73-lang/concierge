import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'panel', 'home', 'headerHome', 'headerSection',
        'wifiPassword', 'wifiPasswordMask', 'wifiPasswordEyeShow', 'wifiPasswordEyeHide',
        'wifiSecondaryPassword', 'wifiSecondaryPasswordMask', 'wifiSecondaryPasswordEyeShow', 'wifiSecondaryPasswordEyeHide',
        'extraConfirm', 'extrasContent',
        'confirmTitle', 'confirmLead', 'confirmItem', 'confirmTotal',
        'confirmPixBlock', 'confirmPixLabel', 'confirmPixHint', 'confirmPixKey', 'confirmPixCopy',
        'confirmWhatsapp', 'confirmBack',
        'extrasRequests', 'extrasRequestsList', 'extrasAvailableList', 'extrasEmpty',
        'homeExtras', 'homeExtrasList',
        'foodExtrasBooked', 'foodExtrasAvailable',
    ];
    static values = {
        code: String,
        guestName: String,
        confirmTitle: String,
        confirmLead: String,
        confirmPixLabel: String,
        confirmPixHint: String,
        confirmWhatsapp: String,
        confirmBack: String,
        copyPix: String,
        pixPayment: String,
        noExtras: String,
        foodServicesEmpty: String,
        tabExtras: String,
        statusRequested: String,
        statusPaid: String,
        statusConfirmed: String,
        statusCancelled: String,
        cancelRequest: String,
        offlineExtraQueued: String,
    };

    connect() {
        this.show('home');
        this.cacheStayPage();
        window.addEventListener('online', () => this.cacheStayPage());
    }

    disconnect() {
        document.body.classList.remove('stay-dialog-open');
    }

    navigate(event) {
        const section = event.currentTarget.dataset.section;
        const label = event.currentTarget.dataset.label || section;
        this.show(section, label);
    }

    show(section, label = '') {
        this.panelTargets.forEach(p => {
            p.classList.toggle('active', p.dataset.section === section);
        });
        if (this.hasHomeTarget) {
            this.homeTarget.hidden = section !== 'home';
        }
        if (this.hasHeaderHomeTarget && this.hasHeaderSectionTarget) {
            const onHome = section === 'home';
            this.headerHomeTarget.hidden = !onHome;
            this.headerSectionTarget.hidden = onHome;
            if (!onHome) {
                this.headerSectionTarget.textContent = label;
            }
        }

        document.dispatchEvent(new CustomEvent('stay:section', { detail: { section } }));
    }

    goHome() {
        this.show('home');
    }

    async requestExtra(event) {
        const btn = event.currentTarget;
        const extraId = parseInt(btn.dataset.extraId, 10);
        const prefix = btn.dataset.qtyPrefix || '';
        const quantity = parseInt(
            document.querySelector(`#${prefix}qty-${extraId}`)?.value
            || document.querySelector(`#qty-${extraId}`)?.value
            || '1',
            10,
        );
        const notes = document.querySelector(`#${prefix}notes-${extraId}`)?.value
            || document.querySelector(`#notes-${extraId}`)?.value
            || null;

        btn.disabled = true;

        if (!navigator.onLine) {
            this.enqueueExtraRequest({ code: this.codeValue, extraId, quantity, notes });
            alert(this.hasOfflineExtraQueuedValue ? this.offlineExtraQueuedValue : 'Request saved — will send when back online.');
            btn.disabled = false;
            return;
        }

        try {
            const response = await fetch('/api/concierge/extras/request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: this.codeValue, extraId, quantity, notes }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            this.showExtraConfirmation(data, extraId);
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
        }
    }

    showExtraConfirmation(data, extraId) {
        if (this.hasConfirmTitleTarget) {
            this.confirmTitleTarget.textContent = this.confirmTitleValue;
        }
        if (this.hasConfirmLeadTarget) {
            this.confirmLeadTarget.textContent = this.confirmLeadValue;
        }
        if (this.hasConfirmItemTarget) {
            this.confirmItemTarget.textContent = `${data.name} ×${data.quantity}`;
        }
        if (this.hasConfirmTotalTarget) {
            this.confirmTotalTarget.textContent = data.totalFormatted;
        }

        if (this.hasConfirmPixBlockTarget) {
            if (data.pixKey) {
                this.confirmPixBlockTarget.hidden = false;
                if (this.hasConfirmPixLabelTarget) {
                    this.confirmPixLabelTarget.textContent = this.confirmPixLabelValue;
                }
                if (this.hasConfirmPixHintTarget) {
                    this.confirmPixHintTarget.textContent = this.confirmPixHintValue;
                }
                if (this.hasConfirmPixKeyTarget) {
                    this.confirmPixKeyTarget.textContent = data.pixKey;
                }
                if (this.hasConfirmPixCopyTarget) {
                    this.confirmPixCopyTarget.textContent = this.copyPixValue;
                    this.confirmPixCopyTarget.dataset.copy = data.pixKey;
                }
            } else {
                this.confirmPixBlockTarget.hidden = true;
            }
        }

        if (this.hasConfirmWhatsappTarget) {
            this.confirmWhatsappTarget.href = data.whatsappUrl;
            this.confirmWhatsappTarget.textContent = `${this.confirmWhatsappValue} ↗`;
        }
        if (this.hasConfirmBackTarget) {
            this.confirmBackTarget.textContent = this.confirmBackValue;
        }

        document.querySelectorAll(`[data-extra-id="${extraId}"]`).forEach((trigger) => {
            const card = trigger.closest('.extras-item-card, .food-service-card');
            if (card) {
                card.remove();
            }
        });

        if (data.isBreakfast && this.hasFoodExtrasAvailableTarget) {
            this.foodExtrasAvailableTarget.querySelectorAll('.food-service-card[data-is-breakfast="1"]').forEach((card) => {
                card.remove();
            });
        }

        this.updateAvailableEmptyState();
        this.updateFoodExtrasEmptyState();

        this.addToMyRequests(data);
        this.addToFoodBooked(data);

        if (this.hasExtrasContentTarget) {
            this.extrasContentTarget.hidden = true;
        }
        if (this.hasExtraConfirmTarget) {
            this.extraConfirmTarget.hidden = false;
        }
    }

    dismissExtraConfirm() {
        if (this.hasExtraConfirmTarget) {
            this.extraConfirmTarget.hidden = true;
        }
        if (this.hasExtrasContentTarget) {
            this.extrasContentTarget.hidden = false;
        }
    }

    addToFoodBooked(data) {
        if (!this.hasFoodExtrasBookedTarget) {
            return;
        }

        this.foodExtrasBookedTarget.hidden = false;

        const meta = data.totalFormatted
            ? `${data.quantity}× · ${data.totalFormatted}`
            : `${data.quantity}×`;

        const article = document.createElement('article');
        article.className = 'food-service-booked';
        article.dataset.requestId = String(data.id);
        article.dataset.extraId = String(data.extraId ?? '');
        const cancelBtn = ['requested', 'paid'].includes(data.status)
            ? `<button type="button" class="btn btn-outline btn-sm food-cancel-btn" data-request-id="${data.id}">${this.cancelRequestValue}</button>`
            : '';
        article.innerHTML = `
            <div>
                <p class="food-service-name font-serif">${data.name}</p>
                <p class="food-service-meta">${meta}</p>
            </div>
            <div class="food-service-booked-aside">
                <span class="status-pill status-${data.status}">${this.statusLabel(data.status)}</span>
                ${cancelBtn}
            </div>
        `;
        this.foodExtrasBookedTarget.appendChild(article);
    }

    updateFoodExtrasEmptyState() {
        if (!this.hasFoodExtrasAvailableTarget) {
            return;
        }

        const hasCards = this.foodExtrasAvailableTarget.querySelector('.food-service-card');
        if (!hasCards && this.foodExtrasAvailableTarget.querySelector('.food-services-empty') === null) {
            const empty = document.createElement('p');
            empty.className = 'food-services-empty';
            empty.textContent = this.hasFoodServicesEmptyValue ? this.foodServicesEmptyValue : this.noExtrasValue;
            this.foodExtrasAvailableTarget.appendChild(empty);
        }
    }

    addToMyRequests(data) {
        if (!this.hasExtrasRequestsListTarget) return;

        if (this.hasExtrasRequestsTarget) {
            this.extrasRequestsTarget.hidden = false;
        }

        const article = document.createElement('article');
        article.className = 'extras-request-card';
        article.dataset.requestId = String(data.id);
        article.dataset.extraId = String(data.extraId ?? '');
        const pixNote = data.pixKey
            ? `<p class="extras-pix-note">${this.pixPaymentValue}: <strong>${data.pixKey}</strong></p>`
            : '';
        const cancelBtn = ['requested', 'paid'].includes(data.status)
            ? `<button type="button" class="btn btn-outline btn-block extras-cancel-btn" data-request-id="${data.id}">${this.cancelRequestValue}</button>`
            : '';
        article.innerHTML = `
            <div class="extras-request-top">
                <div>
                    <p class="extras-item-name font-serif">${data.name}</p>
                    <p class="extras-request-qty">${data.quantity}x</p>
                </div>
                <span class="status-pill status-${data.status}">${this.statusLabel(data.status)}</span>
            </div>
            ${pixNote}
            ${cancelBtn}
        `;
        this.extrasRequestsListTarget.appendChild(article);
        this.addToHomeExtras(data);
    }

    addToHomeExtras(data) {
        if (!this.hasHomeExtrasListTarget) return;

        if (this.hasHomeExtrasTarget) {
            this.homeExtrasTarget.hidden = false;
        }

        const meta = data.totalFormatted
            ? `${data.quantity}× · ${data.totalFormatted}`
            : `${data.quantity}×`;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'stay-card-extra';
        btn.dataset.action = 'click->stay#navigate';
        btn.dataset.section = 'extras';
        btn.dataset.label = this.tabExtrasValue;
        btn.innerHTML = `
            <span class="stay-card-extra-main">
                <span class="stay-card-extra-icon" aria-hidden="true">✦</span>
                <span class="stay-card-extra-copy">
                    <span class="stay-card-extra-name font-serif">${data.name}</span>
                    <span class="stay-card-extra-meta">${meta}</span>
                </span>
            </span>
            <span class="stay-card-extra-aside">
                <span class="status-pill status-pill--on-dark status-${data.status}">${this.statusLabel(data.status)}</span>
                <span class="stay-card-extra-chevron" aria-hidden="true">›</span>
            </span>
        `;
        this.homeExtrasListTarget.appendChild(btn);
    }

    statusLabel(status) {
        const labels = {
            requested: this.statusRequestedValue,
            paid: this.statusPaidValue,
            confirmed: this.statusConfirmedValue,
            cancelled: this.statusCancelledValue,
        };
        return labels[status] || status;
    }

    updateAvailableEmptyState() {
        if (!this.hasExtrasAvailableListTarget) return;

        const hasCards = this.extrasAvailableListTarget.querySelector('.extras-item-card');
        if (hasCards) {
            if (this.hasExtrasEmptyTarget) {
                this.extrasEmptyTarget.hidden = true;
            }
            return;
        }

        if (this.hasExtrasEmptyTarget) {
            this.extrasEmptyTarget.hidden = false;
            return;
        }

        const empty = document.createElement('p');
        empty.className = 'extras-empty';
        empty.setAttribute('data-stay-target', 'extrasEmpty');
        empty.textContent = this.noExtrasValue;
        this.extrasAvailableListTarget.appendChild(empty);
    }

    copyConfirmPix(event) {
        const btn = event.currentTarget;
        const text = btn.dataset.copy;
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            const original = btn.textContent;
            btn.textContent = '✓';
            setTimeout(() => { btn.textContent = original; }, 1500);
        });
    }

    copyText(event) {
        const text = event.currentTarget.dataset.copy;
        const copiedLabel = event.currentTarget.dataset.copiedLabel || '✓';
        navigator.clipboard.writeText(text).then(() => {
            const original = event.currentTarget.textContent;
            event.currentTarget.textContent = copiedLabel;
            setTimeout(() => { event.currentTarget.textContent = original; }, 2000);
        });
    }

    copyIcon(event) {
        const btn = event.currentTarget;
        const text = btn.dataset.copy;
        if (!text) return;

        navigator.clipboard.writeText(text).then(() => {
            btn.classList.add('is-copied');
            setTimeout(() => btn.classList.remove('is-copied'), 1500);
        });
    }

    toggleWifiPassword() {
        if (!this.hasWifiPasswordTarget || !this.hasWifiPasswordMaskTarget) return;

        const reveal = this.wifiPasswordTarget.hidden;
        this.wifiPasswordTarget.hidden = !reveal;
        this.wifiPasswordMaskTarget.hidden = reveal;

        if (this.hasWifiPasswordEyeShowTarget && this.hasWifiPasswordEyeHideTarget) {
            this.wifiPasswordEyeShowTarget.hidden = reveal;
            this.wifiPasswordEyeHideTarget.hidden = !reveal;
        }
    }

    toggleSecondaryWifiPassword() {
        if (!this.hasWifiSecondaryPasswordTarget || !this.hasWifiSecondaryPasswordMaskTarget) return;

        const reveal = this.wifiSecondaryPasswordTarget.hidden;
        this.wifiSecondaryPasswordTarget.hidden = !reveal;
        this.wifiSecondaryPasswordMaskTarget.hidden = reveal;

        if (this.hasWifiSecondaryPasswordEyeShowTarget && this.hasWifiSecondaryPasswordEyeHideTarget) {
            this.wifiSecondaryPasswordEyeShowTarget.hidden = reveal;
            this.wifiSecondaryPasswordEyeHideTarget.hidden = !reveal;
        }
    }

    logout() {
        localStorage.removeItem('stayDetails');
        localStorage.removeItem('accessCode');
        window.location.href = '/';
    }

    cacheStayPage() {
        if (!('serviceWorker' in navigator)) return;

        navigator.serviceWorker.ready.then((registration) => {
            const sw = registration.active;
            if (!sw) return;

            const urls = new Set([window.location.pathname]);

            document.querySelectorAll('img[src]').forEach((img) => {
                try {
                    const url = new URL(img.src, window.location.origin);
                    if (url.origin === window.location.origin) {
                        urls.add(url.pathname);
                    }
                } catch {
                    // ignore invalid URLs
                }
            });

            document.querySelectorAll('img[srcset]').forEach((img) => {
                img.srcset.split(',').forEach((part) => {
                    const src = part.trim().split(/\s+/)[0];
                    try {
                        const url = new URL(src, window.location.origin);
                        if (url.origin === window.location.origin) {
                            urls.add(url.pathname);
                        }
                    } catch {
                        // ignore invalid URLs
                    }
                });
            });

            sw.postMessage({ type: 'CACHE_URLS', urls: [...urls] });
        }).catch((e) => {
            console.warn('Stay cache failed', e);
        });
    }

    enqueueExtraRequest(payload) {
        const key = 'domoPendingExtraRequests';
        let queue = [];

        try {
            const raw = localStorage.getItem(key);
            queue = raw ? JSON.parse(raw) : [];
        } catch {
            queue = [];
        }

        queue.push({ ...payload, queuedAt: Date.now() });
        localStorage.setItem(key, JSON.stringify(queue));
    }
}
