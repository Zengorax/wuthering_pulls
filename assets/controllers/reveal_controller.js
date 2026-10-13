import { Controller } from '@hotwired/stimulus';

/*
 * Fait apparaître les éléments .reveal (fondu + glissement) quand ils entrent dans la fenêtre.
 * Les éléments qui apparaissent ensemble sont décalés en cascade.
 */
export default class extends Controller {
    connect() {
        const targets = [...this.element.querySelectorAll('.reveal')];
        if (this.element.classList.contains('reveal')) {
            targets.unshift(this.element);
        }

        // Après l'envoi d'un formulaire, la page revient identique : on l'affiche tout de suite, sans rejouer l'entrée.
        if (document.documentElement.dataset.quick) {
            targets.forEach((target) => target.classList.add('in', 'no-transition'));
            return;
        }

        this.observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting);
            visible.forEach((entry, index) => {
                if (!entry.target.style.getPropertyValue('--i')) {
                    entry.target.style.setProperty('--i', String(Math.min(index, 5)));
                }
                entry.target.classList.add('in');
                this.observer.unobserve(entry.target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -4% 0px' });

        targets.forEach((target) => {
            if (!target.classList.contains('in')) {
                this.observer.observe(target);
            }
        });
    }

    disconnect() {
        this.observer?.disconnect();
    }
}
