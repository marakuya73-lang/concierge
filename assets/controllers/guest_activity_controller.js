import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { code: String };

    connect() {
        this.onSection = this.onSection.bind(this);
        document.addEventListener('stay:section', this.onSection);
    }

    disconnect() {
        document.removeEventListener('stay:section', this.onSection);
    }

    onSection(event) {
        const { section } = event.detail;
        if (!section) {
            return;
        }

        this.record(section);
    }

    record(section) {
        if (!this.codeValue || !navigator.onLine) {
            return;
        }

        fetch('/api/guest-activity', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            keepalive: true,
            body: JSON.stringify({ code: this.codeValue, section }),
        }).catch(() => {});
    }
}
