// Livewire + Alpine, CSP-safe build: components are registered here, templates only reference them by name.
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.csp.esm';

// Open/close panels (mobile menu). Locks page scroll while open; Escape closes.
Alpine.data('disclosure', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
        document.documentElement.classList.toggle('overflow-hidden', this.open);
    },
    close() {
        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
}));

// Product listing filters: a slide-in panel on mobile; on desktop every change applies at once.
Alpine.data('catalogFilters', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
        document.documentElement.classList.toggle('overflow-hidden', this.open);
    },
    close() {
        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
    changed(event) {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            event.target.form.requestSubmit();
        }
    },
    submitNow(event) {
        event.target.form.requestSubmit();
    },
}));

// Hero slider: arrows, dots and swipe; no autoplay (calmer, and accessible by default).
Alpine.data('slider', () => ({
    index: 0,
    count: 0,
    startX: null,
    init() {
        this.count = this.$el.querySelectorAll('[data-slide]').length;
    },
    // Called from a slide or dot element: x-show="isActive" / :aria-current="isActive"
    isActive() {
        const el = this.$el;
        return Number(el.dataset.slide ?? el.dataset.dot) === this.index;
    },
    next() {
        this.index = (this.index + 1) % this.count;
    },
    previous() {
        this.index = (this.index - 1 + this.count) % this.count;
    },
    goTo(event) {
        this.index = Number(event.currentTarget.dataset.dot);
    },
    touchStart(event) {
        this.startX = event.touches[0].clientX;
    },
    touchEnd(event) {
        if (this.startX === null) return;
        const delta = event.changedTouches[0].clientX - this.startX;
        const forward = document.documentElement.dir === 'rtl' ? delta > 0 : delta < 0;
        if (Math.abs(delta) > 40) forward ? this.next() : this.previous();
        this.startX = null;
    },
}));

// Broken images (deleted file, network error) fall back to the brand placeholder instead of a broken icon.
document.addEventListener(
    'error',
    (event) => {
        const img = event.target;
        if (!(img instanceof HTMLImageElement) || !img.dataset.fallback || img.src.endsWith(img.dataset.fallback)) {
            return;
        }
        img.removeAttribute('srcset');
        img.src = img.dataset.fallback;
    },
    true,
);

Livewire.start();
