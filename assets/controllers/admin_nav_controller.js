import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle'];

    connect() {
        this.onKeydown = this.onKeydown.bind(this);
        this.onMediaChange = this.onMediaChange.bind(this);
        this.mq = window.matchMedia('(max-width: 768px)');
        document.addEventListener('keydown', this.onKeydown);
        this.mq.addEventListener('change', this.onMediaChange);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
        this.mq?.removeEventListener('change', this.onMediaChange);
        document.body.classList.remove('admin-nav-scroll-lock');
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        if (!this.mq.matches) {
            return;
        }
        this.element.classList.add('admin-nav-open');
        document.body.classList.add('admin-nav-scroll-lock');
        this.updateToggle(true);
    }

    close() {
        this.element.classList.remove('admin-nav-open');
        document.body.classList.remove('admin-nav-scroll-lock');
        this.updateToggle(false);
    }

    closeOnNav(event) {
        if (event.target.closest('a') && this.mq.matches) {
            this.close();
        }
    }

    onKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }

    onMediaChange() {
        if (!this.mq.matches) {
            this.close();
        }
    }

    updateToggle(isOpen) {
        if (!this.hasToggleTarget) {
            return;
        }
        this.toggleTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    get isOpen() {
        return this.element.classList.contains('admin-nav-open');
    }
}
