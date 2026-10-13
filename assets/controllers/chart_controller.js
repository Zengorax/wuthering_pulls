import { Controller } from '@hotwired/stimulus';

const SVG_NS = 'http://www.w3.org/2000/svg';

/*
 * Graphiques SVG maison (aucune dépendance) : total par version, personnage / arme, cumul et
 * distribution des pulls par 5★. Les libellés viennent de l'utilisateur : tout passe par
 * setAttribute / textContent, jamais par innerHTML.
 */
export default class extends Controller {
    static targets = ['svg', 'tip', 'tab', 'caption'];
    static values = { data: Object, labels: Object };

    connect() {
        this.mode = 'total';
        this.width = 0;

        this.resizeObserver = new ResizeObserver(() => {
            cancelAnimationFrame(this.raf);
            this.raf = requestAnimationFrame(() => {
                const width = Math.round(this.svgTarget.getBoundingClientRect().width);
                if (width > 0 && Math.abs(width - this.width) > 24) {
                    this.width = width;
                    this.render();
                }
            });
        });
        this.resizeObserver.observe(this.svgTarget);
    }

    disconnect() {
        this.resizeObserver?.disconnect();
        cancelAnimationFrame(this.raf);
    }

    select(event) {
        this.mode = event.params.mode;
        this.tabTargets.forEach((tab) => tab.setAttribute('aria-selected', String(tab === event.currentTarget)));
        this.render();
    }

    // ---------------------------------------------------------------- rendu

    render() {
        const svg = this.svgTarget;
        const width = this.width || 800;
        const height = 250;
        const pad = { left: 38, right: 12, top: 14, bottom: 30 };
        svg.replaceChildren();
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        this.hideTip();

        const { labels, character, weapon, estimated, distribution } = this.dataValue;
        const L = this.labelsValue;
        const plot = { x: pad.left, y: pad.top, w: width - pad.left - pad.right, h: height - pad.top - pad.bottom };

        let categories;
        let max;
        if (this.mode === 'distribution') {
            categories = distribution.map((bucket) => bucket.label);
            max = Math.max(...distribution.map((bucket) => bucket.count), 1);
            const total = distribution.reduce((sum, bucket) => sum + bucket.count, 0);
            this.captionTarget.textContent = `${L.pulls} · ${total} ${L.targets}`;
        } else {
            categories = labels;
            const totals = labels.map((_, i) => character[i] + weapon[i]);
            max = this.mode === 'cumulative'
                ? totals.reduce((sum, value) => sum + value, 0)
                : Math.max(...(this.mode === 'split' ? [...character, ...weapon] : totals), 1);
            this.captionTarget.textContent = `${L.pulls} / version`;
        }
        max = Math.max(this.niceMax(max), 1);

        this.drawGrid(svg, plot, max);

        const band = plot.w / Math.max(categories.length, 1);
        const barWidth = Math.min(54, band * 0.62);
        const yFor = (value) => plot.y + plot.h - (value / max) * plot.h;
        const xCenter = (i) => plot.x + band * i + band / 2;

        categories.forEach((category, i) => {
            const text = this.node('text', { x: xCenter(i), y: height - 9, 'text-anchor': 'middle', class: 'chart__label' }, String(category));
            svg.append(text);
        });

        if (this.mode === 'total') {
            labels.forEach((label, i) => {
                const total = character[i] + weapon[i];
                this.bar(svg, xCenter(i) - barWidth / 2, yFor(total), barWidth, plot.y + plot.h - yFor(total), 'url(#grad-total)', i, `${label} · ${total} ${L.pulls}`, estimated[i]);
            });
        } else if (this.mode === 'split') {
            const half = barWidth / 2 - 1;
            labels.forEach((label, i) => {
                const tip = `${label} · ${L.character} ${character[i]} / ${L.weapon} ${weapon[i]}`;
                this.bar(svg, xCenter(i) - barWidth / 2, yFor(character[i]), half, plot.y + plot.h - yFor(character[i]), 'var(--gold)', i, tip, false);
                this.bar(svg, xCenter(i) + 1, yFor(weapon[i]), half, plot.y + plot.h - yFor(weapon[i]), 'var(--violet)', i, tip, false);
            });
            this.legend([[L.character, 'var(--gold)'], [L.weapon, 'var(--violet)']]);
        } else if (this.mode === 'cumulative') {
            this.drawCumulative(svg, plot, labels, character, weapon, xCenter, yFor, L);
        } else {
            distribution.forEach((bucket, i) => {
                this.bar(svg, xCenter(i) - barWidth / 2, yFor(bucket.count), barWidth, plot.y + plot.h - yFor(bucket.count), 'url(#grad-dist)', i, `${bucket.label} ${L.pulls} · ${bucket.count} ${L.targets}`, false);
            });
        }

        if (this.mode !== 'split') {
            this.legend([]);
        }
        this.defs(svg);
        svg.setAttribute('aria-label', `${this.captionTarget.textContent}`);
    }

    drawCumulative(svg, plot, labels, character, weapon, xCenter, yFor, L) {
        let running = 0;
        const points = labels.map((label, i) => {
            running += character[i] + weapon[i];
            return { x: xCenter(i), y: yFor(running), tip: `${label} · ${running} ${L.pulls}` };
        });
        if (points.length === 0) {
            return;
        }
        const line = points.map((p, i) => `${i ? 'L' : 'M'}${p.x} ${p.y}`).join(' ');
        const baseline = plot.y + plot.h;
        svg.append(this.node('path', { d: `${line} L${points.at(-1).x} ${baseline} L${points[0].x} ${baseline}Z`, fill: 'url(#grad-area)', class: 'chart__area' }));
        svg.append(this.node('path', { d: line, class: 'chart__line' }));
        points.forEach((p) => {
            const dot = this.node('circle', { cx: p.x, cy: p.y, r: 5, class: 'chart__dot' });
            this.hover(dot, p.tip);
            svg.append(dot);
        });
    }

    drawGrid(svg, plot, max) {
        for (let i = 0; i <= 4; i += 1) {
            const y = plot.y + (plot.h * i) / 4;
            svg.append(this.node('line', { x1: plot.x, x2: plot.x + plot.w, y1: y, y2: y, class: 'chart__grid' }));
            const value = Math.round(max - (max * i) / 4);
            svg.append(this.node('text', { x: plot.x - 8, y: y + 4, 'text-anchor': 'end', class: 'chart__label' }, String(value)));
        }
    }

    bar(svg, x, y, w, h, fill, index, tip, estimate) {
        const rect = this.node('rect', {
            x, y, width: Math.max(w, 1), height: Math.max(h, 1), fill, class: `chart__bar${estimate ? ' chart__bar--estimate' : ''}`,
            style: `animation-delay:${index * 60}ms`,
        });
        this.hover(rect, tip);
        svg.append(rect);
    }

    defs(svg) {
        const defs = this.node('defs');
        const gradient = (id, stops) => {
            const g = this.node('linearGradient', { id, x1: 0, y1: 0, x2: 0, y2: 1 });
            stops.forEach(([offset, color, opacity]) => g.append(this.node('stop', { offset, 'stop-color': color, 'stop-opacity': opacity })));
            return g;
        };
        defs.append(
            gradient('grad-total', [[0, '#e8c46a', 1], [1, '#5fe7d6', 0.28]]),
            gradient('grad-dist', [[0, '#b98cf2', 1], [1, '#41aefb', 0.25]]),
            gradient('grad-area', [[0, '#5fe7d6', 0.35], [1, '#5fe7d6', 0]]),
        );
        svg.prepend(defs);
    }

    legend(items) {
        const stage = this.svgTarget.parentElement;
        stage.querySelector('.chart__legend')?.remove();
        if (items.length === 0) {
            return;
        }
        const legend = document.createElement('div');
        legend.className = 'chart__legend';
        items.forEach(([label, color]) => {
            const item = document.createElement('span');
            const swatch = document.createElement('i');
            swatch.style.background = color;
            item.append(swatch, label);
            legend.append(item);
        });
        stage.append(legend);
    }

    // ---------------------------------------------------------------- outils

    niceMax(value) {
        if (value <= 4) {
            return 4;
        }
        const step = 10 ** Math.floor(Math.log10(value / 4));
        const rounded = Math.ceil(value / 4 / step) * step;
        return rounded * 4;
    }

    node(name, attributes = {}, text = null) {
        const element = document.createElementNS(SVG_NS, name);
        Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, String(value)));
        if (text !== null) {
            element.textContent = text;
        }
        return element;
    }

    hover(element, text) {
        element.addEventListener('pointermove', (event) => this.showTip(event, text));
        element.addEventListener('pointerleave', () => this.hideTip());
        element.addEventListener('pointerdown', (event) => this.showTip(event, text));
    }

    showTip(event, text) {
        const stage = this.svgTarget.parentElement.getBoundingClientRect();
        const tip = this.tipTarget;
        tip.textContent = text;
        const left = Math.min(event.clientX - stage.left + 14, stage.width - tip.offsetWidth - 4);
        tip.style.left = `${Math.max(0, left)}px`;
        tip.style.top = `${event.clientY - stage.top - 38}px`;
        tip.classList.add('is-on');
    }

    hideTip() {
        this.tipTarget.classList.remove('is-on');
    }
}
