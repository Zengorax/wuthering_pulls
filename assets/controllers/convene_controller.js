import { Controller } from '@hotwired/stimulus';

/*
 * Écran de révélation 5★ affiché après l'enregistrement d'un personnage 5★ réel.
 */
export default class extends Controller {
    static targets = ['close'];
    static values = { name: String };

    connect() {
        this.onKey = (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        };
        document.addEventListener('keydown', this.onKey);
        document.body.style.overflow = 'hidden';
        this.focusTimer = setTimeout(() => this.closeTarget.focus(), 1400);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKey);
        document.body.style.overflow = '';
        clearTimeout(this.focusTimer);
    }

    close() {
        this.element.classList.add('is-leaving');
        setTimeout(() => {
            document.body.style.overflow = '';
            this.element.remove();
        }, 500);
    }
}
