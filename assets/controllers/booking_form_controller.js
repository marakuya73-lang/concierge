import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'source',
        'rajaaramField',
        'rajaaramSection',
        'rajaaramDuoSection',
        'rajaaramIsDuo',
        'rajaaramDuoField',
        'rajaaramTherapy1Label',
    ];

    static values = {
        rajaaram: String,
    };

    connect() {
        this.sync();
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

        if (this.hasRajaaramTherapy1LabelTarget) {
            this.rajaaramTherapy1LabelTarget.textContent = isDuo ? 'Terapia 1' : 'Terapia';
        }
    }

    isDuoSelected() {
        const checked = this.element.querySelector('input[name*="[rajaaramIsDuo]"]:checked');
        if (checked) {
            return checked.value === '1';
        }

        const select = this.element.querySelector('select[name*="[rajaaramIsDuo]"]');
        if (select) {
            return select.value === '1';
        }

        return false;
    }

    sourceElement() {
        if (this.hasSourceTarget) {
            return this.sourceTarget;
        }

        return this.element.querySelector('[data-booking-form-target="source"], select[name*="[source]"]');
    }
}
