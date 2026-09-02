import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];

    static values = {
        active: { type: String, default: 'settings' },
    };

    connect() {
        this.onHashChange = this.onHashChange.bind(this);
        window.addEventListener('hashchange', this.onHashChange);

        // Defer so hash from redirect (#kitchen-content) is applied after full paint
        requestAnimationFrame(() => this.applyInitialTab());
    }

    applyInitialTab() {
        const hash = window.location.hash.replace('#', '');
        const tab = this.tabFromHash(hash) || this.activeValue;
        this.show(tab, false);
    }

    disconnect() {
        window.removeEventListener('hashchange', this.onHashChange);
    }

    onHashChange() {
        const tab = this.tabFromHash(window.location.hash.replace('#', ''));
        if (tab) {
            this.show(tab, false);
        }
    }

    select(event) {
        event.preventDefault();
        this.show(event.currentTarget.dataset.tab, true);
    }

    show(tab, updateHash) {
        if (!this.tabTargets.some((el) => el.dataset.tab === tab)) {
            return;
        }

        this.tabTargets.forEach((el) => {
            const active = el.dataset.tab === tab;
            el.classList.toggle('is-active', active);
            el.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        this.panelTargets.forEach((el) => {
            const active = el.dataset.tab === tab;
            el.hidden = !active;
            el.classList.toggle('is-active', active);
        });

        if (updateHash) {
            const hash = this.hashForTab(tab);
            const url = hash ? `#${hash}` : window.location.pathname + window.location.search;
            history.replaceState(null, '', url);
        }

        this.activeValue = tab;

        if (updateHash) {
            const html = document.documentElement;
            const previous = html.style.scrollBehavior;
            html.style.scrollBehavior = 'auto';
            window.scrollTo(0, 0);
            html.style.scrollBehavior = previous;
        }
    }

    tabFromHash(hash) {
        if (!hash) {
            return null;
        }

        const legacy = {
            'welcome-content': 'welcome',
            'faq-content': 'faq-rules',
            'rules-content': 'faq-rules',
            'activities-content': 'experiences',
            'kitchen-content': 'food',
        };

        if (legacy[hash]) {
            return legacy[hash];
        }

        return this.tabTargets.some((el) => el.dataset.tab === hash) ? hash : null;
    }

    hashForTab(tab) {
        const map = {
            welcome: 'welcome-content',
            'faq-rules': 'faq-content',
            experiences: 'activities-content',
            food: 'kitchen-content',
            settings: '',
        };

        return map[tab] ?? tab;
    }
}
