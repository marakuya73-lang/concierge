import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'source',
        'rajaaramField',
        'rajaaramSection',
        'rajaaramDuoSection',
        'rajaaramDuoField',
        'rajaaramGuest1Label',
        'extraGuestList',
    ];

    static values = {
        rajaaram: String,
    };

    connect() {
        this.sync();
        this.element.querySelectorAll('input[name*="[rajaaramIsDuo]"]').forEach((radio) => {
            radio.addEventListener('change', () => this.sync());
        });
    }

    sync() {
        const source = this.sourceElement();
        if (!source) {
            return;
        }

        const isRajaaram = source.value === this.rajaaramValue;

        if (this.hasRajaaramSectionTarget) {
            this.rajaaramSectionTarget.hidden = !isRajaaram;
        }

        this.rajaaramFieldTargets.forEach((field) => {
            field.hidden = !isRajaaram;
        });

        this.syncDuo(isRajaaram);
    }

    syncDuo(isRajaaram = true) {
        const isDuo = isRajaaram && this.isDuoSelected();

        if (this.hasRajaaramDuoSectionTarget) {
            this.rajaaramDuoSectionTarget.hidden = !isDuo;
        }

        this.rajaaramDuoFieldTargets.forEach((field) => {
            field.hidden = !isDuo;
        });

        if (this.hasRajaaramGuest1LabelTarget) {
            this.rajaaramGuest1LabelTarget.textContent = isDuo ? 'Terapia 1' : 'Terapia';
        }
    }

    addExtraGuest() {
        if (!this.hasExtraGuestListTarget) {
            return;
        }

        const list = this.extraGuestListTarget;
        const prototype = list.dataset.prototype;
        if (!prototype) {
            return;
        }

        const index = list.dataset.index || String(list.querySelectorAll('.extra-guest-row').length);
        list.insertAdjacentHTML('beforeend', prototype.replace(/__name__/g, index));
        list.dataset.index = String(Number(index) + 1);
    }

    removeExtraGuest(event) {
        event.currentTarget.closest('.extra-guest-row')?.remove();
    }

    isDuoSelected() {
        const checked = this.element.querySelector('input[name*="[rajaaramIsDuo]"]:checked');
        if (!checked) {
            return false;
        }

        return checked.value === '1';
    }

    sourceElement() {
        if (this.hasSourceTarget) {
            return this.sourceTarget;
        }

        return this.element.querySelector('[data-booking-form-target="source"], select[name*="[source]"]');
    }
}
