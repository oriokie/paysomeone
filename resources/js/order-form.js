export function orderForm(options) {
    return {
        options,
        service: options.defaultService,
        deadlineId: options.deadlines[options.defaultService]?.[0]?.id ?? '',
        words: 800,
        pages: 2,
        spacing: 'double',
        writerCategoryId: 2,
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
        quote: null,
        error: '',
        loading: false,
        timer: null,
        step: options.initialStep || 1,

        init() {
            this.schedule();
            this.$watch('service', () => {
                const list = this.options.deadlines[this.service] || [];
                this.deadlineId = list[0]?.id ?? '';
                this.proofreading = this.service === 'editing';
                this.schedule();
            });
        },

        get usesWords() {
            return this.service === 'articles';
        },

        get deadlines() {
            return this.options.deadlines[this.service] || [];
        },

        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.fetchQuote(), 180);
        },

        async fetchQuote() {
            if (! this.deadlineId) {
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
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        service: this.service,
                        deadline_id: this.deadlineId,
                        words: this.words,
                        pages: this.pages,
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

                if (! response.ok) {
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

        next() {
            this.step = Math.min(3, this.step + 1);
        },

        back() {
            this.step = Math.max(1, this.step - 1);
        },
    };
}
