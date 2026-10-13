import { Controller } from '@hotwired/stimulus';

/*
 * Période repliable. L'état est mémorisé pour la session afin de survivre aux navigations Turbo.
 */
export default class extends Controller {
    static targets = ['button', 'body'];
    static values = { key: String };

    connect() {
        let stored = null;
        try {
            stored = sessionStorage.getItem(`wp-${this.keyValue}`);
        } catch {
            // Stockage indisponible : on garde l'état par défaut.
        }
        this.apply(stored !== 'closed');
    }

    toggle() {
        const open = this.element.dataset.state !== 'open';
        this.apply(open);
        try {
            sessionStorage.setItem(`wp-${this.keyValue}`, open ? 'open' : 'closed');
        } catch {
            // Sans stockage, l'état n'est simplement pas mémorisé.
        }
    }

    apply(open) {
        this.element.dataset.state = open ? 'open' : 'closed';
        this.buttonTarget.setAttribute('aria-expanded', String(open));
        this.bodyTarget.toggleAttribute('inert', !open);
    }
}
