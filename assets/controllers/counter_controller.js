import { Controller } from '@hotwired/stimulus';

/*
 * Compteur animé : le texte rendu par le serveur reste la valeur finale (sans JavaScript,
 * ou avec « réduire les animations », rien ne change).
 */
export default class extends Controller {
    static values = {
        to: Number,
        decimals: { type: Number, default: 0 },
        duration: { type: Number, default: 1000 },
    };

    connect() {
        this.final = this.element.textContent;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.dataset.quick) {
            return;
        }

        this.format = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: this.decimalsValue,
            maximumFractionDigits: this.decimalsValue,
        });
        this.element.textContent = this.format.format(0);

        this.observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                this.observer.disconnect();
                this.run();
            }
        }, { threshold: 0.3 });
        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer?.disconnect();
        cancelAnimationFrame(this.raf);
    }

    run() {
        const start = performance.now();
        const step = (now) => {
            const progress = Math.min(1, (now - start) / this.durationValue);
            const eased = 1 - Math.pow(1 - progress, 4);
            this.element.textContent = this.format.format(this.toValue * eased);
            if (progress < 1) {
                this.raf = requestAnimationFrame(step);
            } else {
                this.element.textContent = this.final;
            }
        };
        this.raf = requestAnimationFrame(step);
    }
}
