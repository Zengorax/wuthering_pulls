import { Controller } from '@hotwired/stimulus';

/*
 * Lueur qui suit le curseur (un disque déplacé par transform : aucun recalcul de style sur la page)
 * et ondulation au clic.
 */
export default class extends Controller {
    connect() {
        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.frame = null;
        this.x = window.innerWidth * 0.6;
        this.y = window.innerHeight * 0.2;

        this.glow = document.createElement('div');
        this.glow.className = 'pointer-glow';
        this.glow.setAttribute('aria-hidden', 'true');
        this.element.prepend(this.glow);
        this.place();

        this.onMove = (event) => {
            this.x = event.clientX;
            this.y = event.clientY;
            if (!this.frame) {
                this.frame = requestAnimationFrame(() => {
                    this.frame = null;
                    this.place();
                });
            }
        };

        this.onDown = (event) => {
            if (this.reduced || event.button !== 0) {
                return;
            }
            const ripple = document.createElement('i');
            ripple.className = 'ripple';
            ripple.style.left = `${event.clientX}px`;
            ripple.style.top = `${event.clientY}px`;
            document.body.append(ripple);
            setTimeout(() => ripple.remove(), 850);
        };

        window.addEventListener('pointermove', this.onMove, { passive: true });
        window.addEventListener('pointerdown', this.onDown, { passive: true });
    }

    disconnect() {
        window.removeEventListener('pointermove', this.onMove);
        window.removeEventListener('pointerdown', this.onDown);
        cancelAnimationFrame(this.frame);
        this.glow?.remove();
    }

    place() {
        this.glow.style.transform = `translate3d(${this.x - 360}px, ${this.y - 360}px, 0)`;
    }
}
