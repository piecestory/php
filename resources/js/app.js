// Alpine, CSP-safe build: components are registered here, templates only reference them by name.
// The storefront has no Livewire components, so it loads Alpine alone (Livewire runs only in the staff panel).
import Alpine from '@alpinejs/csp';

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

// Cart / wishlist forms: sent in the background when JavaScript is available; plain POST otherwise.
// Publishes window events so counters, other buttons for the same piece and the toast stay in sync.
Alpine.data('shopForm', () => ({
    busy: false,
    saved: false,
    init() {
        this.saved = this.$el.dataset.saved === 'true';
    },
    syncWishlist(event) {
        if (String(event.detail.productId) === this.$el.dataset.product) this.saved = event.detail.saved;
    },
    async submit(event) {
        const form = event.target;
        if (this.busy) return;
        this.busy = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (response.status === 419 || response.status >= 500) {
                form.submit();
                return;
            }
            const data = await response.json();
            if (data.cart) window.dispatchEvent(new CustomEvent('cart-updated', { detail: data.cart }));
            if ('saved' in data) {
                window.dispatchEvent(new CustomEvent('wishlist-updated', {
                    detail: { count: data.count, saved: data.saved, productId: form.dataset.product },
                }));
            }
            if (data.message) {
                const kind = 'saved' in data ? 'wishlist' : 'cart';
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, ok: data.ok, kind } }));
            }
            if (form.dataset.reload === 'true' && data.ok) window.location.reload();
        } catch {
            form.submit();
        } finally {
            this.busy = false;
        }
    },
}));

// Header counters for cart and wishlist.
Alpine.data('counter', () => ({
    count: 0,
    total: '',
    init() {
        this.count = Number(this.$el.dataset.count || 0);
        this.total = this.$el.dataset.total || '';
    },
    hasItems() {
        return this.count > 0;
    },
    updateCart(event) {
        this.count = event.detail.count;
        this.total = event.detail.total;
    },
    updateWishlist(event) {
        this.count = event.detail.count;
    },
}));

// Short confirmation message after cart / wishlist actions (announced to screen readers).
Alpine.data('toast', () => ({
    visible: false,
    message: '',
    ok: true,
    kind: 'cart',
    timer: null,
    show(event) {
        this.message = event.detail.message;
        this.ok = event.detail.ok !== false;
        this.kind = event.detail.kind || 'cart';
        this.visible = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.visible = false), 4000);
    },
    hide() {
        this.visible = false;
    },
    offerCart() {
        return this.ok && this.kind === 'cart';
    },
}));

// Checkout: shows only the fields the current choices need and keeps the summary in step.
// Without JavaScript every section stays visible and the server applies the same rules.
Alpine.data('checkout', () => ({
    method: '',
    type: '',
    totals: {},
    pickupMethods: [],
    init() {
        const form = this.$el;
        this.totals = JSON.parse(form.dataset.totals || '{}');
        this.pickupMethods = (form.dataset.pickupMethods || '').split(',');
        this.method = form.querySelector('input[name="shipping_method"]:checked')?.value || '';
        this.type = form.querySelector('input[name="order_type"]:checked')?.value || '';
    },
    choose(event) {
        if (event.target.name === 'shipping_method') this.method = event.target.value;
        if (event.target.name === 'order_type') this.type = event.target.value;
    },
    isPickup() {
        return this.pickupMethods.includes(this.method);
    },
    isDelivery() {
        return this.method !== '' && !this.isPickup();
    },
    needsPayment() {
        return this.type !== 'reservation';
    },
    reservesOnly() {
        return this.type === 'reservation';
    },
    isDeposit() {
        return this.type === 'deposit_reservation';
    },
    current() {
        return this.totals[`${this.method}:${this.type}`] || {};
    },
    shippingText() {
        return this.current().shipping || '';
    },
    totalText() {
        return this.current().total || '';
    },
    vatText() {
        return this.current().vat || '';
    },
    depositText() {
        return this.current().deposit || '';
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

// Product gallery: thumbnails, full-screen zoom, arrow keys (direction-aware) and swipe.
Alpine.data('gallery', () => ({
    index: 0,
    count: 0,
    zoomed: false,
    startX: null,
    init() {
        this.count = this.$el.querySelectorAll('figure[data-index]').length;
    },
    isCurrent() {
        return Number(this.$el.dataset.index) === this.index;
    },
    counter() {
        return `${this.index + 1} / ${this.count}`;
    },
    select(event) {
        this.index = Number(event.currentTarget.dataset.index);
    },
    next() {
        this.index = (this.index + 1) % this.count;
    },
    previous() {
        this.index = (this.index - 1 + this.count) % this.count;
    },
    openZoom() {
        this.zoomed = true;
        document.documentElement.classList.add('overflow-hidden');
    },
    closeZoom() {
        this.zoomed = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
    onKey(event) {
        if (!this.zoomed) return;
        const rtl = document.documentElement.dir === 'rtl';
        if (event.key === 'Escape') this.closeZoom();
        if (event.key === 'ArrowRight') rtl ? this.previous() : this.next();
        if (event.key === 'ArrowLeft') rtl ? this.next() : this.previous();
    },
    touchStart(event) {
        this.startX = event.touches[0].clientX;
    },
    touchEnd(event) {
        if (this.startX === null || this.count < 2) return;
        const delta = event.changedTouches[0].clientX - this.startX;
        const forward = document.documentElement.dir === 'rtl' ? delta > 0 : delta < 0;
        if (Math.abs(delta) > 40) forward ? this.next() : this.previous();
        this.startX = null;
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

// Horizontally scrolling menus (account sections on phones): keep the current page's tab in view.
for (const nav of document.querySelectorAll('[data-scroll-active]')) {
    const current = nav.querySelector('[aria-current="page"]');
    if (current && nav.scrollWidth > nav.clientWidth) {
        nav.scrollLeft += current.getBoundingClientRect().left - nav.getBoundingClientRect().left - 16;
    }
}

Alpine.start();
