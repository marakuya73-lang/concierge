import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'rajaaramField', 'rajaaramSection'];

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
    }

    sourceElement() {
        if (this.hasSourceTarget) {
            return this.sourceTarget;
        }

        return this.element.querySelector('[data-booking-form-target="source"], select[name*="[source]"]');
    }
}
