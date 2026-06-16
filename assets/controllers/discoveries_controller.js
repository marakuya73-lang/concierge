import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['card', 'pill'];

    connect() {
        if (this.cardTargets.length > 0 && !this.cardTargets.some((card) => card.classList.contains('is-open'))) {
            this.openCard(this.cardTargets[0]);
        }
    }

    toggle(event) {
        const card = event.currentTarget.closest('[data-discoveries-target="card"]');
        if (!card) return;

        if (card.classList.contains('is-open')) {
            card.classList.remove('is-open');
            this.syncPills(-1);
            return;
        }

        this.openCard(card);
    }

    jump(event) {
        const index = Number.parseInt(event.currentTarget.dataset.index, 10);
        const card = this.cardTargets[index];
        if (!card) return;

        this.openCard(card);
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    openCard(card) {
        const index = Number.parseInt(card.dataset.index, 10);

        this.cardTargets.forEach((item) => {
            const isOpen = item === card;
            item.classList.toggle('is-open', isOpen);
            const toggle = item.querySelector('.discoveries-card-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }
        });

        this.syncPills(index);
    }

    syncPills(activeIndex) {
        if (!this.hasPillTarget) return;

        this.pillTargets.forEach((pill, index) => {
            pill.classList.toggle('is-active', index === activeIndex);
        });
    }
}
