import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'wp-element';

/*
 * Choix de l'élément du thème : change la couleur d'accent de toute l'interface.
 */
export default class extends Controller {
    static targets = ['button', 'menu', 'label', 'dot'];

    connect() {
        this.onOutside = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        this.onKey = (event) => {
            if (event.key === 'Escape' && !this.menuTarget.hidden) {
                this.close();
                this.buttonTarget.focus();
            }
        };
        document.addEventListener('pointerdown', this.onOutside);
        document.addEventListener('keydown', this.onKey);
        this.refresh(document.documentElement.dataset.element || 'tacet');
    }

    disconnect() {
        document.removeEventListener('pointerdown', this.onOutside);
        document.removeEventListener('keydown', this.onKey);
    }

    toggle() {
        const open = this.menuTarget.hidden;
        this.menuTarget.hidden = !open;
        this.buttonTarget.setAttribute('aria-expanded', String(open));
        if (open) {
            this.menuTarget.querySelector('button')?.focus();
        }
    }

    close() {
        this.menuTarget.hidden = true;
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }

    pick(event) {
        const element = event.params.element;
        document.documentElement.dataset.element = element;
        try {
            localStorage.setItem(STORAGE_KEY, element);
        } catch {
            // Le choix reste valable pour la page en cours.
        }
        this.refresh(element);
        this.close();
        this.buttonTarget.focus();
    }

    refresh(element) {
        this.labelTarget.textContent = element.charAt(0).toUpperCase() + element.slice(1);
        this.dotTarget.parentElement.dataset.el = element;
    }
}
