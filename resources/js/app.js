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
            const city = String(item.city || '').split(',')[0].trim();
            const name = String(item.name || '').trim();
            const code = String(item.code || '').trim();
            const label = city || name;

            this.query = label && code ? `${label} (${code})` : (label || code);
            this.hintText = name && city && name !== city ? `${name}` : (name || city || '');
            this.code = code;
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

    Alpine.data('flightBookExtras', (config = {}) => {
        const bagQty = {};
        (config.oldServices || []).forEach((service) => {
            if (! service?.id) {
                return;
            }

            const bag = (config.bagServices || []).find((item) => item.id === service.id);
            if (bag) {
                bagQty[service.id] = Number(service.quantity || 0);
            }
        });

        return {
            bagsOpen: false,
            seatsOpen: false,
            bagServices: config.bagServices || [],
            seatMaps: config.seatMaps || [],
            passengers: config.passengers || [],
            bagQty,
            seatByKey: {},
            activePassengerIndex: 0,
            activeMapIndex: 0,
            baseAmount: Number(config.baseAmount || 0),
            currency: config.currency || 'USD',
            paymentChoice: config.paymentChoice || 'pay_now',
            supportsHold: Boolean(config.supportsHold),
            paypalEnabled: Boolean(config.paypalEnabled),
            platformFeePercent: Number(config.platformFeePercent || 0),
            savedPassengers: config.savedPassengers || [],
            passengerForms: [],

            init() {
                const slots = Number(config.passengerSlots || (config.passengers || []).length || 1);
                const oldPassengers = config.oldPassengers || [];
                const defaults = config.defaultPassenger || {};

                this.passengerForms = Array.from({ length: slots }, (_, index) => {
                    const old = oldPassengers[index] || {};
                    const base = index === 0 ? defaults : {
                        title: 'mr',
                        given_name: '',
                        family_name: '',
                        gender: 'm',
                        born_on: '',
                        email: defaults.email || '',
                        phone_number: '',
                        passport_country: '',
                        passport_number: '',
                        passport_expiry: '',
                    };

                    return {
                        selectedId: 'new',
                        title: old.title || base.title || 'mr',
                        given_name: old.given_name || base.given_name || '',
                        family_name: old.family_name || base.family_name || '',
                        gender: old.gender || base.gender || 'm',
                        born_on: old.born_on || base.born_on || '',
                        email: old.email || base.email || '',
                        phone_number: old.phone_number || base.phone_number || '',
                        passport_country: old.passport_country || base.passport_country || '',
                        passport_number: old.passport_number || base.passport_number || '',
                        passport_expiry: old.passport_expiry || base.passport_expiry || '',
                    };
                });
            },

            blankPassengerForm(email = '') {
                return {
                    selectedId: 'new',
                    title: 'mr',
                    given_name: '',
                    family_name: '',
                    gender: 'm',
                    born_on: '',
                    email: email || '',
                    phone_number: '',
                    passport_country: '',
                    passport_number: '',
                    passport_expiry: '',
                };
            },

            selectSavedPassenger(index, id) {
                if (! this.passengerForms[index]) {
                    return;
                }

                if (id === 'new') {
                    const email = this.passengerForms[index].email || (config.defaultPassenger || {}).email || '';
                    this.passengerForms[index] = this.blankPassengerForm(email);
                    return;
                }

                const saved = this.savedPassengers.find((item) => String(item.id) === String(id));
                if (! saved) {
                    return;
                }

                this.passengerForms[index] = {
                    selectedId: saved.id,
                    title: saved.title || 'mr',
                    given_name: saved.given_name || '',
                    family_name: saved.family_name || '',
                    gender: saved.gender || 'm',
                    born_on: saved.born_on || '',
                    email: saved.email || (config.defaultPassenger || {}).email || '',
                    phone_number: saved.phone_number || '',
                    passport_country: saved.passport_country || '',
                    passport_number: saved.passport_number || '',
                    passport_expiry: saved.passport_expiry || '',
                };
            },

            openSeats() {
                this.seatsOpen = true;
                this.activeMapIndex = 0;
            },

            activeMap() {
                return this.seatMaps[this.activeMapIndex] || null;
            },

            activePassenger() {
                return this.passengers[this.activePassengerIndex] || null;
            },

            activePassengerLabel() {
                const passenger = this.activePassenger();
                return passenger ? `Choosing for ${passenger.label}` : 'Choose a seat';
            },

            changeBag(id, delta) {
                const service = this.bagServices.find((item) => item.id === id);
                if (! service) {
                    return;
                }

                const next = Math.max(0, Math.min(service.maximum_quantity, (this.bagQty[id] || 0) + delta));
                this.bagQty = { ...this.bagQty, [id]: next };
            },

            selectedBags() {
                return this.bagServices
                    .filter((service) => (this.bagQty[service.id] || 0) > 0)
                    .map((service) => ({
                        id: service.id,
                        label: service.description || service.label,
                        quantity: this.bagQty[service.id],
                        amount: Number(service.total_amount) * this.bagQty[service.id],
                    }));
            },

            seatKey(mapId, passengerId) {
                return `${mapId || 'map'}::${passengerId}`;
            },

            serviceForPassenger(el) {
                const passenger = this.activePassenger();
                if (! passenger || ! el?.available_services?.length) {
                    return null;
                }

                return el.available_services.find((service) => service.passenger_id === passenger.id)
                    || el.available_services[0]
                    || null;
            },

            pickSeat(el) {
                const map = this.activeMap();
                const passenger = this.activePassenger();
                const service = this.serviceForPassenger(el);
                if (! map || ! passenger || ! service) {
                    return;
                }

                const key = this.seatKey(map.id || this.activeMapIndex, passenger.id);
                const next = { ...this.seatByKey };

                Object.keys(next).forEach((existingKey) => {
                    if (next[existingKey]?.id === service.id) {
                        delete next[existingKey];
                    }
                });

                if (next[key]?.id === service.id) {
                    delete next[key];
                } else {
                    next[key] = {
                        id: service.id,
                        designator: el.designator,
                        amount: Number(service.total_amount || 0),
                        passengerLabel: passenger.label,
                        passengerId: passenger.id,
                        mapId: map.id || this.activeMapIndex,
                    };
                }

                this.seatByKey = next;
            },

            seatClass(el) {
                if (el.type === 'aisle') {
                    return 'is-aisle';
                }
                if (el.type !== 'seat') {
                    return 'is-fixture';
                }
                if (! el.available || ! this.serviceForPassenger(el)) {
                    return 'is-taken';
                }

                const map = this.activeMap();
                const passenger = this.activePassenger();
                const service = this.serviceForPassenger(el);
                const key = this.seatKey(map?.id || this.activeMapIndex, passenger?.id);
                if (service && this.seatByKey[key]?.id === service.id) {
                    return 'is-selected';
                }
                if (Object.values(this.seatByKey).some((seat) => seat.id === service?.id)) {
                    return 'is-taken';
                }

                return Number(service?.total_amount || 0) > 0 ? 'is-paid' : 'is-free';
            },

            selectedSeats() {
                return Object.values(this.seatByKey);
            },

            selectedServices() {
                const bags = Object.entries(this.bagQty)
                    .filter(([, qty]) => qty > 0)
                    .map(([id, quantity]) => ({ id, quantity }));
                const seats = this.selectedSeats().map((seat) => ({ id: seat.id, quantity: 1 }));

                return [...bags, ...seats];
            },

            extrasTotal() {
                const bags = this.selectedBags().reduce((sum, bag) => sum + bag.amount, 0);
                const seats = this.selectedSeats().reduce((sum, seat) => sum + Number(seat.amount || 0), 0);

                return bags + seats;
            },

            subtotal() {
                return this.baseAmount + this.extrasTotal();
            },

            platformFeeAmount() {
                return Math.round(this.subtotal() * (this.platformFeePercent / 100) * 100) / 100;
            },

            grandTotal() {
                return Math.round((this.subtotal() + this.platformFeeAmount()) * 100) / 100;
            },

            formatMoney(amount) {
                try {
                    return new Intl.NumberFormat(undefined, {
                        style: 'currency',
                        currency: this.currency,
                        maximumFractionDigits: 2,
                    }).format(Number(amount || 0));
                } catch (e) {
                    return `${this.currency} ${Number(amount || 0).toFixed(2)}`;
                }
            },

            syncServices() {
                return true;
            },
        };
    });
});

window.Alpine = Alpine;

Alpine.start();
