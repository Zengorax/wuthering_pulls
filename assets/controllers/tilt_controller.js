import { Controller } from '@hotwired/stimulus';

/*
 * Inclinaison 3D et reflet qui suivent le pointeur (souris uniquement, jamais en mode « réduire les animations »).
 */
export default class extends Controller {
    connect() {
        const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!finePointer || reduced) {
            return;
        }

        this.glare = document.createElement('span');
        this.glare.className = 'glare';
        this.glare.setAttribute('aria-hidden', 'true');
        this.element.prepend(this.glare);

        this.onMove = (event) => {
            this.pointer = event;
            if (this.frame) {
                return;
            }
            this.frame = requestAnimationFrame(() => {
                this.frame = null;
                const rect = this.element.getBoundingClientRect();
                const x = (this.pointer.clientX - rect.left) / rect.width;
                const y = (this.pointer.clientY - rect.top) / rect.height;
                this.element.style.setProperty('--ry', `${(x - 0.5) * 7}deg`);
                this.element.style.setProperty('--rx', `${(0.5 - y) * 7}deg`);
                this.element.style.setProperty('--gx', `${x * 100}%`);
                this.element.style.setProperty('--gy', `${y * 100}%`);
            });
        };
        this.onLeave = () => {
            cancelAnimationFrame(this.frame);
            this.frame = null;
            this.element.style.setProperty('--rx', '0deg');
            this.element.style.setProperty('--ry', '0deg');
        };

        this.element.addEventListener('pointermove', this.onMove);
        this.element.addEventListener('pointerleave', this.onLeave);
    }

    disconnect() {
        this.element.removeEventListener('pointermove', this.onMove);
        this.element.removeEventListener('pointerleave', this.onLeave);
        this.glare?.remove();
    }
}
