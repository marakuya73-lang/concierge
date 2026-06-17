import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { url: String };

    open(event) {
        if (event.target.closest('a, button')) {
            return;
        }

        window.location.assign(this.urlValue);
    }

    keydown(event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        window.location.assign(this.urlValue);
    }
}
