import './bootstrap';

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('airportPicker', (initial = '') => ({
        query: initial || '',
        code: '',
        open: false,
        suggestions: [],
        active: 0,
        suggestUrl: '',

        hintText: '',

        init() {
            this.suggestUrl = this.$el.dataset.suggestUrl || '/airports/suggest';
        },

        async search() {
            const q = this.query.trim();
            this.code = '';
            this.hintText = '';

            if (q.length < 1) {
                this.suggestions = [];
                this.open = false;
                return;
            }

            try {
                const response = await fetch(`${this.suggestUrl}?q=${encodeURIComponent(q)}`);
                this.suggestions = await response.json();
                this.active = 0;
                this.open = this.suggestions.length > 0;
            } catch {
                this.suggestions = [];
                this.open = false;
            }
        },

        select(item) {
            const city = String(item.city || item.name || '').split(',')[0].trim();
            this.query = city && item.code ? `${city}, ${item.code}` : (item.name || item.code);
            this.hintText = item.name || item.city || '';
            this.code = item.code;
            this.open = false;
            this.suggestions = [];
        },

        move(step) {
            if (! this.suggestions.length) {
                return;
            }

            this.active = (this.active + step + this.suggestions.length) % this.suggestions.length;
        },

        pickHighlighted() {
            if (this.open && this.suggestions[this.active]) {
                this.select(this.suggestions[this.active]);
            }
        },
    }));

    Alpine.data('homeSearch', () => ({
        tab: 'flights',
        trip: 'one_way',
        adults: 1,
        cabin: 'economy',
        travellersOpen: false,
        promo: '',
        promoOpen: false,
        departDate: '',
        returnDate: '',

        picker(refName) {
            const el = this.$refs[refName];

            if (! el) {
                return null;
            }

            const scoped = el.matches('[x-data]') ? el : el.querySelector('[x-data]');

            return scoped ? window.Alpine.$data(scoped) : null;
        },

        swapAirports() {
            const from = this.picker('fromPicker');
            const to = this.picker('toPicker');

            if (! from || ! to) {
                return;
            }

            [from.query, to.query] = [to.query, from.query];
            [from.code, to.code] = [to.code, from.code];
            [from.hintText, to.hintText] = [to.hintText, from.hintText];
            from.open = false;
            to.open = false;
        },
    }));

    Alpine.data('flightSearch', () => ({
        picker(refName) {
            const el = this.$refs[refName];

            if (! el) {
                return null;
            }

            const scoped = el.matches('[x-data]') ? el : el.querySelector('[x-data]');

            return scoped ? window.Alpine.$data(scoped) : null;
        },

        swapAirports() {
            const from = this.picker('fromPicker');
            const to = this.picker('toPicker');

            if (! from || ! to) {
                return;
            }

            [from.query, to.query] = [to.query, from.query];
            [from.code, to.code] = [to.code, from.code];
            from.open = false;
            to.open = false;
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
