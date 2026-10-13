import { Controller } from '@hotwired/stimulus';

/*
 * Orbe du hero : onde sonore dessinée en continu et légère parallaxe des anneaux à la souris.
 */
export default class extends Controller {
    static targets = ['stage', 'wave', 'layerFar', 'layerMid'];

    connect() {
        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.visible = true;
        this.phase = 0;
        this.tilt = { x: 0, y: 0, tx: 0, ty: 0 };

        this.onMove = (event) => {
            const rect = this.stageTarget.getBoundingClientRect();
            this.tilt.tx = (event.clientX - (rect.left + rect.width / 2)) / window.innerWidth;
            this.tilt.ty = (event.clientY - (rect.top + rect.height / 2)) / window.innerHeight;
        };
        window.addEventListener('pointermove', this.onMove, { passive: true });

        this.observer = new IntersectionObserver(([entry]) => {
            this.visible = entry.isIntersecting;
            if (this.visible) {
                this.loop();
            }
        });
        this.observer.observe(this.stageTarget);
        this.loop();
    }

    disconnect() {
        cancelAnimationFrame(this.raf);
        this.observer?.disconnect();
        window.removeEventListener('pointermove', this.onMove);
    }

    loop(time = 0) {
        cancelAnimationFrame(this.raf);
        if (!this.last || time - this.last >= 33) {
            this.last = time;
            this.drawWave();
        }
        this.tilt.x += (this.tilt.tx - this.tilt.x) * 0.06;
        this.tilt.y += (this.tilt.ty - this.tilt.y) * 0.06;
        this.layerFarTarget.style.transform = `translate(${this.tilt.x * -26}px, ${this.tilt.y * -26}px)`;
        this.layerMidTarget.style.transform = `translate(${this.tilt.x * 20}px, ${this.tilt.y * 20}px)`;

        if (this.visible && !this.reduced && !document.hidden) {
            this.phase += 0.02;
            this.raf = requestAnimationFrame((t) => this.loop(t));
        }
    }

    drawWave() {
        const left = 112;
        const right = 288;
        const base = 318;
        let path = `M ${left} ${base}`;
        for (let x = left; x <= right; x += 3) {
            const envelope = Math.sin(((x - left) / (right - left)) * Math.PI);
            const y = base
                + Math.sin(x * 0.11 + this.phase) * 13 * envelope
                + Math.sin(x * 0.27 - this.phase * 1.7) * 5 * envelope
                + Math.sin(x * 0.05 + this.phase * 0.6) * 6 * envelope;
            path += ` L ${x} ${y.toFixed(1)}`;
        }
        this.waveTarget.setAttribute('d', path);
    }
}
