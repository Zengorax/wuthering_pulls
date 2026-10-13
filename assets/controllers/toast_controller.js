import { Controller } from '@hotwired/stimulus';

const LIFETIME = 5000;

export default class extends Controller {
    connect() {
        this.timer = setTimeout(() => this.close(), LIFETIME);
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    close() {
        clearTimeout(this.timer);
        this.element.classList.add('is-leaving');
        setTimeout(() => this.element.remove(), 420);
    }
}
