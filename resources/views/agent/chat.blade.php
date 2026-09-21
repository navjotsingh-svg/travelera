<x-public-layout title="Flight agent">
    <div class="agent-page">
        <section class="agent-stage">
            <img
                src="{{ asset('images/sky-with-clouds-sunset-with.webp') }}"
                alt=""
                class="agent-stage-photo"
                aria-hidden="true"
            >
            <div class="agent-stage-veil" aria-hidden="true"></div>
            <div class="agent-stage-glow" aria-hidden="true"></div>

            <div class="agent-stage-copy">
                <p class="agent-brand-mark agent-rise">Travelera</p>
                <h1 class="agent-title agent-rise agent-rise-2">Find, refine, then book yourself</h1>
                <p class="agent-lede agent-rise agent-rise-3">
                    Cheapest or fastest, plus nonstop, budget, and time filters. I only hand you the checkout link — never auto-pay.
                </p>
            </div>
        </section>

        <section class="agent-panel-wrap">
            <div
                class="agent-shell agent-rise agent-rise-4"
                x-data="flightAgentChat({
                    endpoint: @js(route('agent.chat.store')),
                    checkoutEndpoint: @js(route('agent.checkout')),
                    welcome: @js($welcome),
                    enabled: @js($enabled),
                })"
                x-init="boot()"
            >
                <header class="agent-toolbar">
                    <div class="agent-toolbar-identity">
                        <span class="agent-avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 17.5l6.2-2.1 4.1-7.3 2.2 2.2-7.3 4.1-2.1 6.2z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.2 8.3l2.5-2.5a1.5 1.5 0 012.1 0l.4.4a1.5 1.5 0 010 2.1l-2.5 2.5"/>
                            </svg>
                        </span>
                        <div>
                            <p class="agent-toolbar-title">Flight agent</p>
                            <p class="agent-toolbar-sub">Phase 2 · refine · no auto-pay</p>
                        </div>
                    </div>
                    <div class="agent-toolbar-actions">
                        <button type="button" class="agent-toolbar-reset" @click="resetChat()" :disabled="loading">New search</button>
                        <a href="{{ route('flights.index') }}" class="agent-toolbar-link">Classic search</a>
                    </div>
                </header>

                <div class="agent-thread" x-ref="thread" role="log" aria-live="polite">
                    <template x-for="(item, index) in messages" :key="index">
                        <div class="agent-row" :class="item.role === 'user' ? 'is-user' : 'is-bot'">
                            <template x-if="item.role !== 'user'">
                                <span class="agent-row-mark" aria-hidden="true">T</span>
                            </template>
                            <div class="agent-row-body">
                                <div class="agent-bubble">
                                    <p x-text="item.content"></p>
                                </div>

                                <template x-if="item.offers && item.offers.length">
                                    <div class="agent-offers">
                                        <template x-for="offer in item.offers" :key="offer.id">
                                            <article class="agent-ticket">
                                                <div class="agent-ticket-head">
                                                    <div class="agent-ticket-airline">
                                                        <template x-if="offer.airline_logo">
                                                            <img :src="offer.airline_logo" alt="" class="agent-ticket-logo">
                                                        </template>
                                                        <div class="min-w-0">
                                                            <p class="agent-ticket-carrier" x-text="offer.airline"></p>
                                                            <p class="agent-ticket-meta" x-text="(offer.flight_number || '') + (offer.fare_brand ? ' · ' + offer.fare_brand : '')"></p>
                                                        </div>
                                                    </div>
                                                    <div class="agent-ticket-price">
                                                        <p class="agent-ticket-amount">
                                                            <span x-text="offer.total_currency"></span>
                                                            <span x-text="offer.total_amount"></span>
                                                        </p>
                                                        <p class="agent-ticket-badge" x-show="offer.badge" x-text="offer.badge"></p>
                                                        <p class="agent-ticket-fee" x-show="offer.platform_fee_percent > 0">incl. platform fee</p>
                                                    </div>
                                                </div>

                                                <p class="agent-ticket-why" x-show="offer.why" x-text="offer.why"></p>

                                                <div class="agent-ticket-route">
                                                    <div>
                                                        <p class="agent-ticket-code" x-text="offer.origin"></p>
                                                        <p class="agent-ticket-time" x-text="offer.departure_label || '—'"></p>
                                                    </div>
                                                    <div class="agent-ticket-flightpath" aria-hidden="true">
                                                        <span x-text="offer.duration || '—'"></span>
                                                        <div class="agent-ticket-rail">
                                                            <i></i>
                                                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 00-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                                                            <i></i>
                                                        </div>
                                                        <span x-text="offer.stops === 0 ? 'Non-stop' : (offer.stops + ' stop' + (offer.stops > 1 ? 's' : ''))"></span>
                                                    </div>
                                                    <div class="agent-ticket-dest">
                                                        <p class="agent-ticket-code" x-text="offer.destination"></p>
                                                        <p class="agent-ticket-time" x-text="offer.arrival_label || '—'"></p>
                                                    </div>
                                                </div>

                                                <div class="agent-ticket-actions">
                                                    <button type="button" class="agent-book-btn" @click="selectOffer(offer)" :disabled="loading">
                                                        <span x-text="loading ? 'Opening…' : 'Continue here'"></span>
                                                        <svg x-show="!loading" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                                                    </button>
                                                </div>
                                            </article>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="item.checkout">
                                    <div class="agent-checkout">
                                        <template x-if="item.checkout.auth_required">
                                            <a :href="item.checkout.login_url" class="agent-selected-cta">Sign in to finish in chat</a>
                                        </template>
                                        <template x-if="!item.checkout.auth_required">
                                            <form class="agent-checkout-form" @submit.prevent="submitCheckout(item.checkout, $event)">
                                                <p class="agent-checkout-kicker" x-text="(item.checkout.summary.total_currency || '') + ' ' + (item.checkout.summary.total_amount || '')"></p>
                                                <template x-for="(slot, index) in checkoutSlots(item.checkout)" :key="index">
                                                    <fieldset class="agent-passenger">
                                                        <legend x-text="'Passenger ' + (index + 1)"></legend>
                                                        <div class="agent-passenger-grid">
                                                            <select class="agent-field" x-model="slot.title" required>
                                                                <option value="mr">Mr</option>
                                                                <option value="ms">Ms</option>
                                                                <option value="mrs">Mrs</option>
                                                                <option value="miss">Miss</option>
                                                                <option value="dr">Dr</option>
                                                            </select>
                                                            <select class="agent-field" x-model="slot.gender" required>
                                                                <option value="m">Male</option>
                                                                <option value="f">Female</option>
                                                            </select>
                                                            <input class="agent-field" x-model="slot.given_name" required placeholder="First name" maxlength="80">
                                                            <input class="agent-field" x-model="slot.family_name" required placeholder="Last name" maxlength="80">
                                                            <input class="agent-field" type="date" x-model="slot.born_on" required>
                                                            <input class="agent-field" type="email" x-model="slot.email" required placeholder="Email">
                                                            <input class="agent-field agent-field-wide" x-model="slot.phone_number" required placeholder="Phone" maxlength="30">
                                                        </div>
                                                        <div class="agent-saved" x-show="item.checkout.saved_passengers && item.checkout.saved_passengers.length">
                                                            <template x-for="saved in item.checkout.saved_passengers" :key="saved.id">
                                                                <button type="button" class="agent-chip" @click="applySaved(slot, saved)">
                                                                    <span x-text="saved.label"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </fieldset>
                                                </template>
                                                <div class="agent-pay-choices">
                                                    <label><input type="radio" value="pay_now" x-model="item.checkout.payment_choice"> Pay myself with PayPal</label>
                                                    <label x-show="item.checkout.supports_hold"><input type="radio" value="hold" x-model="item.checkout.payment_choice"> Hold fare, no charge yet</label>
                                                </div>
                                                <button type="submit" class="agent-book-btn" :disabled="loading">Confirm in chat</button>
                                            </form>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="item.payment && item.payment.approve_url">
                                    <a :href="item.payment.approve_url" class="agent-selected-cta" target="_blank" rel="noopener">Open PayPal to pay</a>
                                </template>
                                <template x-if="item.payment && item.payment.booking_url && !item.payment.approve_url">
                                    <a :href="item.payment.booking_url" class="agent-selected-cta">View booking</a>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="agent-row is-bot" x-show="loading" x-cloak>
                        <span class="agent-row-mark" aria-hidden="true">T</span>
                        <div class="agent-bubble agent-typing">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>

                <div class="agent-dock">
                    <div class="agent-suggestions" x-show="!loading && suggestions.length" x-cloak>
                        <template x-for="chip in suggestions" :key="chip.message">
                            <button type="button" class="agent-chip" @click="useSuggestion(chip.message, chip.action || null)">
                                <span x-text="chip.label"></span>
                            </button>
                        </template>
                    </div>

                    <form class="agent-composer" @submit.prevent="send()" :aria-busy="loading.toString()">
                        <label class="sr-only" for="agent-message">Message</label>
                        <input
                            id="agent-message"
                            type="text"
                            x-model="draft"
                            :disabled="loading || !enabled"
                            maxlength="500"
                            placeholder="Try: cheapest nonstop DEL to DXB under 500 on 12 Oct"
                            class="agent-input"
                            autocomplete="off"
                        >
                        <button type="submit" class="agent-send" :disabled="loading || !draft.trim() || !enabled" aria-label="Send">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <script>
        function flightAgentChat({ endpoint, checkoutEndpoint, welcome, enabled }) {
            return {
                endpoint,
                checkoutEndpoint,
                welcome,
                enabled: enabled !== false,
                draft: '',
                loading: false,
                messages: [],
                suggestions: [],
                forms: {},
                boot() {
                    this.messages = [{ role: 'assistant', content: this.welcome, offers: [], selected: null, checkout: null, payment: null }];
                    this.suggestions = [
                        { label: 'Cheapest LON → NYC', message: 'Cheapest flight from London to New York next Friday' },
                        { label: 'Nonstop DEL → DXB', message: 'Cheapest nonstop Delhi to Dubai tomorrow under 500' },
                        { label: 'Compare BOM → SIN', message: 'Compare cheapest and fastest from Mumbai to Singapore on 2026-11-05' },
                    ];
                    this.$nextTick(() => this.scrollDown());
                },
                useSuggestion(text, action = null) {
                    this.draft = text;
                    this.send(action);
                },
                selectOffer(offer) {
                    if (!offer?.id || this.loading) return;
                    this.send('select', 'select', offer.id, `Continue here ${offer.airline || ''} ${offer.flight_number || ''}`.trim());
                },
                checkoutSlots(checkout) {
                    if (!checkout._slots) {
                        const count = Math.max(1, Number(checkout.passenger_count || 1));
                        const base = checkout.defaults || {};
                        checkout._slots = Array.from({ length: count }, () => ({ ...base }));
                        checkout.payment_choice = checkout.payment_choice || 'pay_now';
                    }
                    return checkout._slots;
                },
                applySaved(slot, saved) {
                    Object.assign(slot, {
                        title: saved.title || 'mr',
                        given_name: saved.given_name || '',
                        family_name: saved.family_name || '',
                        gender: saved.gender || 'm',
                        born_on: saved.born_on || '',
                        email: saved.email || slot.email || '',
                        phone_number: saved.phone_number || '',
                    });
                },
                async submitCheckout(checkout) {
                    if (this.loading || !checkout?.offer_id) return;
                    const passengers = this.checkoutSlots(checkout).map((slot) => ({ ...slot }));
                    this.messages.push({ role: 'user', content: 'Confirm passengers', offers: [], checkout: null, payment: null });
                    this.loading = true;
                    this.scrollDown(true);
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(this.checkoutEndpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                offer_id: checkout.offer_id,
                                passengers,
                                payment_choice: checkout.payment_choice || 'pay_now',
                            }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const message = data.message || data.errors?.['passengers.0.given_name']?.[0] || 'Check the passenger details and try again.';
                            this.messages.push({ role: 'assistant', content: message, offers: [], checkout: null, payment: null });
                        } else {
                            this.messages.push({
                                role: 'assistant',
                                content: data.reply || 'Ready when you are.',
                                offers: [],
                                checkout: null,
                                payment: data.payment || null,
                            });
                        }
                    } catch (e) {
                        this.messages.push({ role: 'assistant', content: 'Network error — please try again.', offers: [], checkout: null, payment: null });
                    } finally {
                        this.loading = false;
                        this.scrollDown(true);
                    }
                },
                resetChat() {
                    this.send('reset', 'reset', null, 'Start over');
                },
                async send(action = null, forcedAction = null, offerId = null, forcedText = null) {
                    const text = (forcedText || this.draft || '').trim();
                    const act = forcedAction || action;
                    if ((!text && act !== 'reset' && act !== 'select') || this.loading || !this.enabled) return;

                    const display = text || (act === 'reset' ? 'Start over' : 'Continue');
                    this.messages.push({ role: 'user', content: display, offers: [], selected: null, checkout: null, payment: null });
                    this.draft = '';
                    this.loading = true;
                    this.scrollDown(true);

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(this.endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                message: text || display,
                                action: act,
                                offer_id: offerId,
                            }),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const message = data.message || data.errors?.message?.[0] || 'Something went wrong. Please try again.';
                            this.messages.push({ role: 'assistant', content: message, offers: [], selected: null, checkout: null, payment: null });
                        } else {
                            this.messages.push({
                                role: 'assistant',
                                content: data.reply || 'Here are some options.',
                                offers: Array.isArray(data.offers) ? data.offers : [],
                                selected: data.selected || null,
                                checkout: data.checkout || null,
                                payment: null,
                            });
                            if (Array.isArray(data.suggestions)) {
                                this.suggestions = data.suggestions;
                            }
                        }
                    } catch (e) {
                        this.messages.push({
                            role: 'assistant',
                            content: 'Network error — please try again in a moment.',
                            offers: [],
                            selected: null,
                            checkout: null,
                            payment: null,
                        });
                    } finally {
                        this.loading = false;
                        this.scrollDown(true);
                    }
                },
                scrollDown(aggressive = false) {
                    const pin = () => {
                        const el = this.$refs.thread;
                        if (!el) return;
                        el.scrollTop = el.scrollHeight + 240;
                        const last = el.lastElementChild;
                        if (last) {
                            last.scrollIntoView({ block: 'end', inline: 'nearest', behavior: 'auto' });
                        }
                    };

                    this.$nextTick(() => {
                        pin();
                        requestAnimationFrame(() => {
                            pin();
                            if (aggressive) {
                                setTimeout(pin, 40);
                                setTimeout(pin, 120);
                                setTimeout(pin, 280);
                                setTimeout(pin, 480);
                            }
                        });
                    });
                },
            };
        }
    </script>
</x-public-layout>
