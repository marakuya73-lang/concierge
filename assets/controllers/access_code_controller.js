import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'error', 'submit'];

    connect() {
        this.inputs = this.inputTargets;
        this.inputs[0]?.focus();
    }

    onInput(event) {
        const input = event.target;
        const index = this.inputs.indexOf(input);
        const char = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(-1);
        input.value = char;

        if (char && index < this.inputs.length - 1) {
            this.inputs[index + 1].focus();
        }
    }

    onKeydown(event) {
        const input = event.target;
        const index = this.inputs.indexOf(input);
        if (event.key === 'Backspace' && !input.value && index > 0) {
            this.inputs[index - 1].focus();
        }
    }

    onPaste(event) {
        event.preventDefault();
        const text = event.clipboardData.getData('text').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 5);
        for (let i = 0; i < this.inputs.length; i++) {
            this.inputs[i].value = text[i] || '';
        }
        this.inputs[Math.min(text.length, this.inputs.length - 1)]?.focus();
    }

    async submit(event) {
        event.preventDefault();
        const code = this.inputs.map(i => i.value).join('');
        if (code.length < 5) {
            this.showError('Por favor, insira o código completo de 5 caracteres.');
            return;
        }

        this.submitTarget.disabled = true;
        this.errorTarget.hidden = true;

        try {
            const response = await fetch('/verify-code', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Código inválido');

            localStorage.setItem('stayDetails', JSON.stringify(data));
            localStorage.setItem('accessCode', code);
            window.location.href = '/stay/' + code;
        } catch (err) {
            this.showError(err.message);
            this.submitTarget.disabled = false;
        }
    }

    showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }
}
