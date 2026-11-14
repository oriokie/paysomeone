export function orderForm(options) {
    const catalog = options.catalog || {};
    const old = options.old || {};

    return {
        options,
        catalog,
        old,
        steps: ['Service', 'Details', 'Deadline and length', 'Extras', 'Review'],
        step: options.initialStep || 1,
        maxReached: options.initialStep || 1,
        service: options.defaultService,
        deadlineId: options.deadlines[options.defaultService]?.[0]?.id ?? '',
        words: 800,
        pages: 2,
        slides: 0,
        charts: 0,
        sourcesCount: 0,
        spacing: 'double',
        writerCategoryId: 2,
        paperTypeId: catalog.articles?.[0]?.id ?? '',
        englishTypeId: catalog.englishTypes?.[0]?.id ?? '',
        targetAudienceId: catalog.audiences?.[0]?.id ?? '',
        writingToneId: catalog.tones?.[0]?.id ?? '',
        writingStyleId: catalog.styles?.[0]?.id ?? '',
        contentFeelId: catalog.feels?.[0]?.id ?? '',
        software: catalog.software?.[0]?.title ?? '',
        deliverableId: catalog.deliverables?.[0]?.id ?? '',
        title: old.title || '',
        brief: old.brief || '',
        keywords: old.keywords || '',
        name: old.name || '',
        email: old.email || '',
        preferredWriter: false,
        samples: false,
        progressive: false,
        sources: false,
        complex: false,
        proofreading: options.defaultService === 'editing',
        vip: false,
        plagiarism: false,
        outline: false,
        couponCode: '',
        couponOpen: false,
        quote: null,
        error: '',
        fieldErrors: {},
        loading: false,
        timer: null,
        bump: false,
        lastTotal: null,
        summaryOpen: false,
        typeQuery: '',
        files: [],
        readOnly: !!options.readOnly,

        init() {
            this.schedule();
            this.$watch('service', () => {
                const list = this.options.deadlines[this.service] || [];
                this.deadlineId = list[0]?.id ?? '';
                this.paperTypeId = this.paperTypes[0]?.id ?? '';
                this.proofreading = this.service === 'editing';
                this.schedule();
            });
            this.$watch('quote', () => this.popTotal());
        },

        get usesWords() {
            return this.service === 'articles';
        },

        get isSoftware() {
            return this.service === 'software';
        },

        get isPages() {
            return !this.usesWords && !this.isSoftware;
        },

        get deadlines() {
            return this.options.deadlines[this.service] || [];
        },

        get paperTypes() {
            const list = this.usesWords ? (this.catalog.articles || []) : (this.catalog.documents || []);
            const q = this.typeQuery.trim().toLowerCase();
            if (!q) {
                return list;
            }
            return list.filter((type) => type.title.toLowerCase().includes(q));
        },

        get serviceLabel() {
            return this.options.services[this.service] || 'Order';
        },

        get deadline() {
            return this.deadlines.find((item) => String(item.id) === String(this.deadlineId)) || null;
        },

        get dueLabel() {
            if (!this.deadline) {
                return '';
            }
            const when = new Date(Date.now() + (Number(this.deadline.hours) || 0) * 3600 * 1000);
            return when.toLocaleString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
        },

        get pageHint() {
            const pages = Math.max(1, Math.round(Number(this.words || 0) / 275));
            return `About ${pages} ${pages === 1 ? 'page' : 'pages'} at 275 words a page.`;
        },

        get quantityLabel() {
            if (this.usesWords) {
                return `${Number(this.words || 0).toLocaleString('en-US')} words`;
            }
            const parts = [`${this.pages} ${this.pages === 1 ? 'page' : 'pages'}`];
            if (this.isPages && this.slides) {
                parts.push(`${this.slides} slides`);
            }
            if (this.isPages && this.charts) {
                parts.push(`${this.charts} charts`);
            }
            return parts.join(', ');
        },

        get filteredLines() {
            return (this.quote?.lines || []).filter((line) => line.key !== 'discount');
        },

        get discountLine() {
            return (this.quote?.lines || []).find((line) => line.key === 'discount') || null;
        },

        popTotal() {
            const next = this.quote?.amount_due;
            if (next === undefined || next === this.lastTotal) {
                return;
            }
            this.lastTotal = next;
            this.bump = false;
            requestAnimationFrame(() => {
                this.bump = true;
            });
        },

        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.fetchQuote(), 160);
        },

        async fetchQuote() {
            if (!this.deadlineId) {
                this.quote = null;
                this.error = this.deadlines.length ? '' : 'Rates for this service are not in the price list yet.';
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(this.options.quoteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        service: this.service,
                        deadline_id: this.deadlineId,
                        words: this.words,
                        pages: this.pages,
                        slides: this.slides,
                        charts: this.charts,
                        spacing: this.spacing,
                        writer_category_id: this.writerCategoryId,
                        preferred_writer: this.preferredWriter,
                        samples: this.samples,
                        progressive: this.progressive,
                        sources: this.sources,
                        complex: this.complex,
                        proofreading: this.proofreading,
                        vip: this.vip,
                        plagiarism: this.plagiarism,
                        outline: this.outline,
                        coupon_code: this.couponCode,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.quote = null;
                    this.error = payload.message || Object.values(payload.errors || {})[0]?.[0] || 'The price could not be calculated.';
                    return;
                }

                this.quote = payload;
            } catch (error) {
                this.quote = null;
                this.error = 'The price could not be calculated. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        money(amount) {
            const value = Number(amount || 0);
            return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        },

        rateLabel(deadline) {
            const rate = Number(deadline.rate || 0);
            const digits = deadline.unit === 'word' ? 4 : 2;
            return `$${rate.toFixed(digits)} / ${deadline.unit}`;
        },

        go(next) {
            if (next > this.maxReached || next < 1) {
                return;
            }
            this.step = next;
            this.summaryOpen = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        next() {
            if (!this.validate()) {
                return;
            }
            this.step = Math.min(5, this.step + 1);
            this.maxReached = Math.max(this.maxReached, this.step);
            this.summaryOpen = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        back() {
            this.step = Math.max(1, this.step - 1);
            this.summaryOpen = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        validate() {
            this.fieldErrors = {};
            if (this.step === 2) {
                if (!this.title || this.title.trim().length < 2) {
                    this.fieldErrors.title = 'Add a working title.';
                }
                if (!this.brief || this.brief.trim().length < 30) {
                    this.fieldErrors.brief = 'Write at least 30 characters so the brief is usable.';
                }
            }
            if (this.step === 3) {
                if (!this.deadlineId) {
                    this.fieldErrors.deadline = 'Choose a deadline.';
                }
                if (this.usesWords && (this.words < 100 || this.words > 20000)) {
                    this.fieldErrors.words = 'Enter a word count between 100 and 20,000.';
                }
                if (!this.usesWords && (this.pages < 1 || this.pages > 200)) {
                    this.fieldErrors.pages = 'Enter at least 1 page.';
                }
            }
            return Object.keys(this.fieldErrors).length === 0;
        },

        nudge(field, delta, min, max) {
            const next = Math.min(max, Math.max(min, Number(this[field] || 0) + delta));
            this[field] = next;
            this.schedule();
        },

        takeFiles(list) {
            this.files = [...list].map((file) => file.name);
        },

        chipOn(active) {
            return active
                ? 'border-navy bg-navy text-white'
                : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300';
        },
    };
}

export function paymentDesk(initial) {
    return {
        phase: initial || 'choose',
        provider: 'mpesa',
        waiting: initial === 'waiting',
        init() {
            if (this.phase === 'waiting' || this.phase === 'success' || this.phase === 'failed') {
                this.waiting = this.phase === 'waiting';
            }
        },
        send(event) {
            if (this.provider !== 'mpesa') {
                return;
            }
            this.phase = 'waiting';
            this.waiting = true;
        },
    };
}
