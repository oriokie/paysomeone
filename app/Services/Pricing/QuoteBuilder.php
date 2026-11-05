<?php

namespace App\Services\Pricing;

use App\Models\Coupon;
use App\Models\Deadline;
use App\Support\RateCatalog;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Validation\ValidationException;

class QuoteBuilder
{
    public function __construct(
        private readonly PriceCalculator $calculator,
        private readonly RateCatalog $catalog,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function build(array $input): Quote
    {
        $service = (string) ($input['service'] ?? '');

        if (! Services::known($service)) {
            throw ValidationException::withMessages(['service' => 'Choose a service.']);
        }

        $deadline = Deadline::query()->find((int) ($input['deadline_id'] ?? 0));

        if (! $deadline || ! $this->catalog->allows($deadline, $service)) {
            throw ValidationException::withMessages(['deadline_id' => 'Choose a turnaround from the list.']);
        }

        $coupon = $this->coupon(isset($input['coupon_code']) ? (string) $input['coupon_code'] : null);
        $tier = (int) ($input['writer_category_id'] ?? 2);
        if (! isset(WriterTiers::TIERS[$tier])) {
            $tier = 2;
        }
        $percent = WriterTiers::percent($tier);
        $payout = PayoutRates::fromConfiguration();

        if (Services::usesWords($service)) {
            $words = (int) ($input['words'] ?? 0);
            if ($words < 100 || $words > 20000) {
                throw ValidationException::withMessages(['words' => 'Enter a word count between 100 and 20,000.']);
            }

            return $this->calculator->quoteWords(
                pricePerWord: (float) $deadline->price_per_page,
                words: $words,
                writerPercent: $percent,
                coupon: $coupon,
                payout: $payout,
            );
        }

        $pages = (int) ($input['pages'] ?? 0);
        $min = max(1, (int) ($deadline->min_pages ?: 1));
        if ($pages < $min || $pages > 200) {
            throw ValidationException::withMessages(['pages' => "Enter at least {$min} page".($min === 1 ? '' : 's').'.']);
        }

        $spacing = ($input['spacing'] ?? 'double') === 'single' ? 'single' : 'double';

        return $this->calculator->quotePages(
            pricePerPage: (float) $deadline->price_per_page,
            hours: (int) $deadline->hrs_customer,
            pages: $pages,
            slides: $this->units($input, 'slides'),
            charts: $this->units($input, 'charts'),
            spacing: $spacing,
            writerPercent: $percent,
            writerCategoryId: $tier,
            preferredWriter: (bool) ($input['preferred_writer'] ?? false),
            samples: (bool) ($input['samples'] ?? false),
            progressive: (bool) ($input['progressive'] ?? false),
            sources: (bool) ($input['sources'] ?? false),
            complex: (bool) ($input['complex'] ?? false),
            proofreading: (bool) ($input['proofreading'] ?? false),
            vip: (bool) ($input['vip'] ?? false),
            plagiarism: (bool) ($input['plagiarism'] ?? false),
            outline: (bool) ($input['outline'] ?? false),
            coupon: $coupon,
            payout: $payout,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function units(array $input, string $key): int
    {
        $value = (int) ($input[$key] ?? 0);

        if ($value < 0 || $value > 100) {
            throw ValidationException::withMessages([$key => 'Enter a number from 0 to 100.']);
        }

        return $value;
    }

    private function coupon(?string $code): ?CouponSpec
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $siteId = (int) config('legacy.site_id');
        $query = Coupon::query()->where('coupon_code', $code);
        $coupon = (clone $query)->where('site_id', $siteId)->first() ?? $query->first();

        if (! $coupon || ! $coupon->isUsable()) {
            throw ValidationException::withMessages(['coupon_code' => 'That code is not active.']);
        }

        $type = (int) $coupon->coupon_type;
        if (! in_array($type, [CouponSpec::PERCENT, CouponSpec::AMOUNT, CouponSpec::PRICE_PER_PAGE], true)) {
            throw ValidationException::withMessages(['coupon_code' => 'This code cannot be used on the order form yet.']);
        }

        return new CouponSpec($type, (float) $coupon->coupon_value);
    }
}
