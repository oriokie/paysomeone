export function orderForm(options) {
    const catalog = options.catalog || {};

    return {
        options,
        catalog,
        service: options.defaultService,
        deadlineId: options.deadlines[options.defaultService]?.[0]?.id ?? '',
        words: 800,
        pages: 2,
        slides: 0,
        charts: 0,
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
        preferredWriter: false,
        samples: false,
        progressive: false,
        sources: false,
        complex: false,
        proofreading: false,
        vip: false,
        plagiarism: false,
        outline: false,
        couponCode: '',
        quote: null,
        error: '',
        loading: false,
        timer: null,

        init() {
            this.schedule();
            this.$watch('service', () => {
                const list = this.options.deadlines[this.service] || [];
                this.deadlineId = list[0]?.id ?? '';
                const types = this.paperTypes;
                this.paperTypeId = types[0]?.id ?? '';
                this.proofreading = this.service === 'editing';
                this.schedule();
            });
        },

        get usesWords() {
            return this.service === 'articles';
        },

        get isSoftware() {
            return this.service === 'software';
        },

        get isPages() {
            return ! this.usesWords && ! this.isSoftware;
        },

        get deadlines() {
            return this.options.deadlines[this.service] || [];
        },

        get paperTypes() {
            return this.usesWords ? (this.catalog.articles || []) : (this.catalog.documents || []);
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
    };
}
