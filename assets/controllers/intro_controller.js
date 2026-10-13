import { Controller } from '@hotwired/stimulus';

const DURATION = 1900;

/*
 * Écran de synchronisation affiché une seule fois par session (le gabarit active data-intro avant le premier rendu).
 */
export default class extends Controller {
    static targets = ['bar', 'pct'];

    connect() {
        if (document.documentElement.dataset.intro !== '1') {
            this.element.remove();
            return;
        }

        this.skip = () => this.finish();
        window.addEventListener('keydown', this.skip, { once: true });
        this.element.addEventListener('pointerdown', this.skip, { once: true });

        const start = performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - start) / DURATION);
            const eased = progress < 0.5 ? 2 * progress * progress : 1 - Math.pow(-2 * progress + 2, 2) / 2;
            this.barTarget.style.width = `${eased * 100}%`;
            this.pctTarget.textContent = String(Math.round(eased * 100));
            if (progress < 1) {
                this.raf = requestAnimationFrame(step);
            } else {
                this.finish();
            }
        };
        this.raf = requestAnimationFrame(step);
    }

    disconnect() {
        cancelAnimationFrame(this.raf);
    }

    finish() {
        if (this.done) {
            return;
        }
        this.done = true;
        cancelAnimationFrame(this.raf);
        try {
            sessionStorage.setItem('wp-intro', '1');
        } catch {
            // Sans stockage, l'écran réapparaîtra au prochain rechargement complet.
        }
        this.element.classList.add('is-leaving');
        setTimeout(() => {
            delete document.documentElement.dataset.intro;
            this.element.remove();
        }, 800);
    }
}
