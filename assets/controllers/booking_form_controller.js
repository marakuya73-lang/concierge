import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'source',
        'rajaaramField',
        'rajaaramSection',
        'rajaaramDuoSection',
        'rajaaramGuest1NameRow',
        'rajaaramDuoField',
        'rajaaramGuest1Label',
        'guestNameTopRow',
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

        if (this.hasRajaaramGuest1NameRowTarget) {
            this.rajaaramGuest1NameRowTarget.hidden = !isDuo;
        }

        this.rajaaramDuoFieldTargets.forEach((field) => {
            if (field === this.rajaaramGuest1NameRowTarget) {
                return;
            }

            field.hidden = !isDuo;
        });

        if (this.hasRajaaramGuest1LabelTarget) {
            this.rajaaramGuest1LabelTarget.textContent = isDuo ? 'Hóspede 1' : 'Terapia';
        }

        if (this.hasGuestNameTopRowTarget) {
            this.guestNameTopRowTarget.hidden = isDuo;
        }
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
