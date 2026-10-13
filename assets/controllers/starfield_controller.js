import { Controller } from '@hotwired/stimulus';

/*
 * Ciel étoilé : étoiles scintillantes à 3 profondeurs (parallaxe à la souris),
 * poussière de résonance de la couleur d'accent et étoiles filantes occasionnelles.
 */
export default class extends Controller {
    connect() {
        this.ctx = this.element.getContext('2d');
        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        this.shooting = null;
        this.accent = '#5fe7d6';
        this.lastAccentRead = 0;

        this.onResize = () => this.resize();
        this.onMove = (event) => {
            this.mouse.tx = event.clientX / window.innerWidth - 0.5;
            this.mouse.ty = event.clientY / window.innerHeight - 0.5;
        };
        this.onVisibility = () => {
            if (!document.hidden) {
                this.loop(performance.now());
            }
        };

        window.addEventListener('resize', this.onResize);
        window.addEventListener('pointermove', this.onMove, { passive: true });
        document.addEventListener('visibilitychange', this.onVisibility);

        this.resize();
        this.loop(performance.now());
    }

    disconnect() {
        cancelAnimationFrame(this.raf);
        window.removeEventListener('resize', this.onResize);
        window.removeEventListener('pointermove', this.onMove);
        document.removeEventListener('visibilitychange', this.onVisibility);
    }

    resize() {
        const ratio = 1; // de minuscules points : inutile de payer le coût du rendu HiDPI
        this.w = window.innerWidth;
        this.h = window.innerHeight;
        this.element.width = this.w * ratio;
        this.element.height = this.h * ratio;
        this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        const count = Math.min(130, Math.floor((this.w * this.h) / 15000));
        this.stars = Array.from({ length: count }, () => ({
            x: Math.random() * this.w,
            y: Math.random() * this.h,
            depth: [0.25, 0.55, 1][Math.floor(Math.random() * 3)],
            r: Math.random() * 1.3 + 0.25,
            phase: Math.random() * Math.PI * 2,
            speed: 0.6 + Math.random() * 1.6,
            gold: Math.random() < 0.12,
        }));
        this.dust = Array.from({ length: Math.min(24, Math.floor(this.w / 60)) }, () => ({
            x: Math.random() * this.w,
            y: Math.random() * this.h,
            r: Math.random() * 1.6 + 0.4,
            vy: 0.08 + Math.random() * 0.3,
            a: 0.15 + Math.random() * 0.4,
        }));
        this.draw(performance.now());
    }

    loop(time) {
        cancelAnimationFrame(this.raf);
        if (document.hidden) {
            return;
        }
        // 30 images par seconde suffisent pour un scintillement et libèrent le processeur.
        if (!this.lastDraw || time - this.lastDraw >= 33) {
            this.lastDraw = time;
            this.draw(time);
        }
        if (!this.reduced) {
            this.raf = requestAnimationFrame((t) => this.loop(t));
        }
    }

    draw(time) {
        const { ctx, w, h } = this;
        if (time - this.lastAccentRead > 1000) {
            this.lastAccentRead = time;
            this.accent = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || this.accent;
        }

        this.mouse.x += (this.mouse.tx - this.mouse.x) * 0.04;
        this.mouse.y += (this.mouse.ty - this.mouse.y) * 0.04;
        ctx.clearRect(0, 0, w, h);

        const t = time / 1000;
        for (const star of this.stars) {
            const x = star.x - this.mouse.x * 36 * star.depth;
            const y = star.y - this.mouse.y * 24 * star.depth;
            const twinkle = 0.45 + 0.55 * Math.sin(t * star.speed + star.phase);
            ctx.globalAlpha = (0.25 + 0.6 * star.depth) * twinkle;
            ctx.fillStyle = star.gold ? '#f7e6ae' : '#cfe3ff';
            const size = star.r * (0.6 + star.depth * 0.6) * 2;
            ctx.fillRect(x, y, size, size);
        }

        ctx.fillStyle = this.accent;
        for (const mote of this.dust) {
            mote.y -= mote.vy;
            mote.x += Math.sin(mote.y * 0.012) * 0.12;
            if (mote.y < -6) {
                mote.y = h + 6;
                mote.x = Math.random() * w;
            }
            ctx.globalAlpha = mote.a;
            ctx.beginPath();
            ctx.arc(mote.x, mote.y, mote.r, 0, Math.PI * 2);
            ctx.fill();
        }

        this.drawShootingStar(ctx, time);
        ctx.globalAlpha = 1;
    }

    drawShootingStar(ctx, time) {
        if (this.reduced) {
            return;
        }
        if (!this.shooting && Math.random() < 0.0022) {
            this.shooting = { x: Math.random() * this.w * 0.8, y: Math.random() * this.h * 0.4, life: 0 };
        }
        const star = this.shooting;
        if (!star) {
            return;
        }
        star.life += 1;
        star.x += 11;
        star.y += 5.5;
        const alpha = Math.max(0, 1 - star.life / 55);
        const gradient = ctx.createLinearGradient(star.x, star.y, star.x - 130, star.y - 65);
        gradient.addColorStop(0, `rgba(247, 230, 174, ${alpha})`);
        gradient.addColorStop(1, 'rgba(247, 230, 174, 0)');
        ctx.globalAlpha = 1;
        ctx.strokeStyle = gradient;
        ctx.lineWidth = 1.6;
        ctx.beginPath();
        ctx.moveTo(star.x, star.y);
        ctx.lineTo(star.x - 130, star.y - 65);
        ctx.stroke();
        if (star.life > 55) {
            this.shooting = null;
        }
    }
}
