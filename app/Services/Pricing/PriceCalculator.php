<?php

declare(strict_types=1);

namespace App\Services\Pricing;

/**
 * Customer-facing price rules used by the current order system.
 *
 * Page work (resumes, business documents, editing, rewriting):
 * - A single-spaced page is charged at twice the deadline's per-page rate.
 *   Double spacing uses the rate as stored.
 * - A slide or chart is half that deadline rate, rounded to the cent first,
 *   and is not doubled for single spacing.
 * - Add-ons are percentages of the page/slide/chart subtotal, or flat fees.
 * - Progressive delivery is 10%. It is offered only when the subtotal is
 *   at least $200 and the deadline is at least 120 hours. It is added
 *   automatically when the subtotal is at least $600 and the deadline is
 *   at least 168 hours.
 * - Copy of sources is 10% of the subtotal, with a $14.95 minimum.
 * - A specialist subject is +20%. Expert proofreading and a draft/outline
 *   are each +25%. A named writer is +10%. A writer tier adds its own
 *   percent (0, 25, or 30 on the current site).
 * - Flat fees: writer's earlier samples $5, VIP support $9.99,
 *   plagiarism report $9.99.
 *
 * Article work is price-per-word times the word count, plus the writer-tier
 * percent. A percent coupon is taken from the word subtotal only, which is
 * how the current article orders are stored.
 *
 * Programming and spreadsheet-style item prices are the catalogue price
 * times the deadline modifier.
 *
 * A percent coupon is type 1, a fixed amount is type 3, and a coupon that
 * replaces the per-page (or per-word) rate is type 9. Free-unit coupons
 * (type 5) reduce whole pages, slides, or charts but always leave one unit
 * paid. A writer-tier coupon (type 7) waives that tier when it matches.
 * Type 8 is stored on the current site and is not applied by its calculator;
 * it is not applied here either.
 *
 * The current site's main calculate() method never actually calls its
 * free-unit step, so a type 5 coupon there may not change the stored total.
 * This calculator does apply it.
 *
 * Amounts are rounded to the cent the same way: round(amount * 100) / 100.
 */
final class PriceCalculator
{
    public const PAGES = 1;

    public const SLIDES = 2;

    public const CHARTS = 31;

    public const WRITER_TIER = 3;

    public const SAMPLES = 4;

    public const PROGRESSIVE = 5;

    public const DISCOUNT = 6;

    public const SOURCES = 21;

    public const COMPLEX = 37;

    public const PROOFREADING = 41;

    public const VIP = 44;

    public const PLAGIARISM = 45;

    public const OUTLINE = 46;

    public const PREFERRED_WRITER = 48;

    public const SAMPLES_FEE = 5.0;

    public const VIP_FEE = 9.99;

    public const PLAGIARISM_FEE = 9.99;

    public const PROGRESSIVE_PERCENT = 10.0;

    public const SOURCES_PERCENT = 10.0;

    public const SOURCES_MINIMUM = 14.95;

    public const COMPLEX_PERCENT = 20.0;

    public const PROOFREADING_PERCENT = 25.0;

    public const OUTLINE_PERCENT = 25.0;

    public const PREFERRED_PERCENT = 10.0;

    public static function money(float $amount): float
    {
        return round($amount * 100) / 100;
    }

    /**
     * @param  list<CouponSpec>  $winbacks
     */
    public function quotePages(
        float $pricePerPage,
        int $hours,
        int $pages,
        int $slides = 0,
        int $charts = 0,
        string $spacing = 'double',
        int $writerPercent = 0,
        int $writerCategoryId = 0,
        bool $preferredWriter = false,
        bool $samples = false,
        bool $progressive = false,
        bool $sources = false,
        bool $complex = false,
        bool $proofreading = false,
        bool $vip = false,
        bool $plagiarism = false,
        bool $outline = false,
        ?CouponSpec $coupon = null,
        array $winbacks = [],
        ?PayoutRates $payout = null,
    ): Quote {
        $payout ??= new PayoutRates;
        $pricePerPage = $this->rateAfterCoupon($pricePerPage, $coupon);
        $spacingFactor = $spacing === 'single' ? 2 : 1;
        $pageRate = self::money($pricePerPage * $spacingFactor);
        $slideRate = self::money($pricePerPage * 0.5);
        $chartRate = $slideRate;

        $pages = max(0, $pages);
        $slides = max(0, $slides);
        $charts = max(0, $charts);

        $pageCost = self::money($pages * $pageRate);
        $slideCost = self::money($slides * $slideRate);
        $chartCost = self::money($charts * $chartRate);

        $freePages = $this->freeUnits($winbacks, self::PAGES, $pages);
        $freeSlides = $this->freeUnits($winbacks, self::SLIDES, $slides);
        $freeCharts = $this->freeUnits($winbacks, self::CHARTS, $charts);

        $pageReduction = self::money($pageRate * $freePages);
        $slideReduction = self::money($slideRate * $freeSlides);
        $chartReduction = self::money($chartRate * $freeCharts);

        $pageCost = self::money($pageCost - $pageReduction);
        $slideCost = self::money($slideCost - $slideReduction);
        $chartCost = self::money($chartCost - $chartReduction);

        $base = self::money($pageCost + $slideCost + $chartCost);

        $notes = [];
        $progressiveAvailable = $base >= 200 && $hours >= 120;
        $progressiveForced = $base >= 600 && $hours >= 168;
        $progressivePrice = $progressiveAvailable
            ? self::money($base * self::PROGRESSIVE_PERCENT / 100)
            : 0.0;

        if (! $progressiveAvailable) {
            $notes[] = 'Progressive delivery is available on work of $200 or more with a deadline of 5 days or longer.';
        } elseif ($progressiveForced) {
            $notes[] = 'Progressive delivery is included for work of $600 or more with a deadline of 7 days or longer.';
        }

        $sourcesPrice = self::money(max(self::SOURCES_MINIMUM, $base * self::SOURCES_PERCENT / 100));
        $tierPrice = self::money($base * $writerPercent / 100);
        $tierWaived = $this->tierIsFree($winbacks, $writerCategoryId);
        if ($tierWaived) {
            $tierPrice = 0.0;
        }

        $lines = [];
        if ($pages > 0) {
            $label = $pages.' '.($pages === 1 ? 'page' : 'pages').' × $'.number_format($pageRate, 2);
            if ($freePages > 0) {
                $label .= ' ('.$freePages.' included)';
            }
            $lines[] = new Line('pages', $label, $pageCost, $this->unitPayout($payout, 'writer', $pageRate, $pages, $spacingFactor), $this->unitPayout($payout, 'editor', $pageRate, $pages, $spacingFactor));
        }
        if ($slides > 0) {
            $lines[] = new Line('slides', $slides.' '.($slides === 1 ? 'slide' : 'slides').' × $'.number_format($slideRate, 2), $slideCost);
        }
        if ($charts > 0) {
            $lines[] = new Line('charts', $charts.' '.($charts === 1 ? 'chart' : 'charts').' × $'.number_format($chartRate, 2), $chartCost);
        }

        $extras = [
            $this->optionalLine('samples', 'Earlier writing samples', $samples, self::SAMPLES_FEE, $payout->writerSamplesPercent, $payout->editorSamplesPercent),
            $this->optionalLine('progressive', 'Progressive delivery', $progressiveForced || ($progressive && $progressiveAvailable), $progressivePrice, $payout->writerProgressivePercent, $payout->editorProgressivePercent),
            $this->optionalLine('sources', 'Copy of sources used', $sources, $sourcesPrice, $payout->writerSourcesPercent, $payout->editorSourcesPercent),
            $this->optionalLine('complex', 'Specialist or technical subject', $complex, self::money($base * self::COMPLEX_PERCENT / 100), $payout->writerComplexPercent, $payout->editorComplexPercent),
            $this->optionalLine('proofreading', 'Expert proofreading', $proofreading, self::money($base * self::PROOFREADING_PERCENT / 100), $payout->writerProofPercent, $payout->editorProofPercent),
            $this->optionalLine('outline', 'Draft or outline', $outline, self::money($base * self::OUTLINE_PERCENT / 100), $payout->writerOutlinePercent, $payout->editorOutlinePercent),
            $this->optionalLine('preferred', 'A specific writer', $preferredWriter, self::money($base * self::PREFERRED_PERCENT / 100), $payout->writerPreferredPercent, 0),
            $this->optionalLine('vip', 'VIP support', $vip, self::VIP_FEE, $payout->writerVipPercent, $payout->editorVipPercent),
            $this->optionalLine('plagiarism', 'Plagiarism report', $plagiarism, self::PLAGIARISM_FEE, $payout->writerPlagiarismPercent, $payout->editorPlagiarismPercent),
        ];

        if ($tierPrice > 0) {
            $lines[] = new Line(
                'writer',
                'Writer tier',
                $tierPrice,
                self::money($tierPrice * $payout->writerCategoryPercent / 100),
                self::money($tierPrice * $payout->editorCategoryPercent / 100),
            );
        }

        foreach ($extras as $line) {
            if ($line instanceof Line && $line->amount != 0.0) {
                $lines[] = $line;
            }
        }

        $raw = 0.0;
        foreach ($lines as $line) {
            $raw = self::money($raw + $line->amount);
        }

        $discount = $this->discountAmount($coupon, $raw);
        if ($discount != 0.0) {
            $lines[] = new Line('discount', 'Discount', $discount);
        }

        return $this->finish($lines, $base, $discount, $notes, $progressiveAvailable, $progressiveForced, $progressiveAvailable ? null : $notes[0] ?? null, $progressivePrice);
    }

    public function quoteWords(
        float $pricePerWord,
        int $words,
        int $writerPercent = 0,
        ?CouponSpec $coupon = null,
        ?PayoutRates $payout = null,
    ): Quote {
        $payout ??= new PayoutRates;
        $words = max(0, $words);

        if ($coupon?->type === CouponSpec::PRICE_PER_PAGE) {
            $pricePerWord = (float) $coupon->value;
            $coupon = null;
        }

        $wordCost = self::money($pricePerWord * $words);
        $tier = self::money($wordCost * $writerPercent / 100);
        $lines = [
            new Line('words', number_format($words).' words × $'.number_format($pricePerWord, 4), $wordCost),
        ];
        if ($tier > 0) {
            $lines[] = new Line('writer', 'Writer tier', $tier);
        }

        $running = self::money($wordCost + $tier);
        $discountBase = $wordCost;
        $discount = 0.0;
        if ($coupon?->type === CouponSpec::AMOUNT) {
            $discount = -self::money($coupon->value);
        } elseif ($coupon?->type === CouponSpec::PERCENT) {
            $discount = -self::money($discountBase * $coupon->value / 100);
        }
        if ($discount != 0.0) {
            $lines[] = new Line('discount', 'Discount', $discount);
        }

        $writerPay = $this->wordPayout($payout->writerWordMode, $payout->writerWordValue, $words, $running);
        $editorPay = $this->wordPayout($payout->editorWordMode, $payout->editorWordValue, $words, $running);

        $total = 0.0;
        foreach ($lines as $line) {
            $total = self::money($total + $line->amount);
        }

        return new Quote(
            base: $wordCost,
            extras: $tier,
            discount: $discount,
            total: $total,
            writerPay: $writerPay,
            editorPay: $editorPay,
            lines: $lines,
            notes: [],
            progressiveAvailable: false,
            progressiveForced: false,
            progressiveReason: null,
            progressivePrice: 0,
        );
    }

    public function quoteItem(float $itemPrice, float $modifier, ?CouponSpec $coupon = null): Quote
    {
        $cost = self::money($itemPrice * $modifier);
        $lines = [new Line('item', 'Project price', $cost)];
        $discount = $this->discountAmount($coupon, $cost);
        if ($discount != 0.0) {
            $lines[] = new Line('discount', 'Discount', $discount);
        }

        return $this->finish($lines, $cost, $discount, [], false, false, null, 0);
    }

    private function rateAfterCoupon(float $rate, ?CouponSpec $coupon): float
    {
        if ($coupon?->type === CouponSpec::PRICE_PER_PAGE) {
            return (float) $coupon->value;
        }

        return $rate;
    }

    /**
     * @param  list<CouponSpec>  $winbacks
     */
    private function freeUnits(array $winbacks, int $serviceId, int $quantity): int
    {
        $free = 0;
        foreach ($winbacks as $coupon) {
            if ($coupon->type === CouponSpec::FREE_UNIT && $coupon->serviceTypeId === $serviceId) {
                $free += $coupon->quantity;
            }
        }

        return min($free, max(0, $quantity - 1));
    }

    /**
     * @param  list<CouponSpec>  $winbacks
     */
    private function tierIsFree(array $winbacks, int $writerCategoryId): bool
    {
        foreach ($winbacks as $coupon) {
            if ($coupon->type === CouponSpec::WRITER_CATEGORY && (int) $coupon->writerCategoryId === $writerCategoryId) {
                return true;
            }
        }

        return false;
    }

    private function discountAmount(?CouponSpec $coupon, float $raw): float
    {
        if ($coupon === null) {
            return 0.0;
        }

        if ($coupon->type === CouponSpec::AMOUNT) {
            return -self::money($coupon->value);
        }

        if ($coupon->type === CouponSpec::PERCENT) {
            return -self::money($raw * $coupon->value / 100);
        }

        return 0.0;
    }

    private function optionalLine(string $key, string $label, bool $on, float $amount, float $writerPercent, float $editorPercent): ?Line
    {
        if (! $on || $amount == 0.0) {
            return null;
        }

        return new Line(
            $key,
            $label,
            self::money($amount),
            self::money($amount * $writerPercent / 100),
            self::money($amount * $editorPercent / 100),
        );
    }

    private function unitPayout(PayoutRates $payout, string $who, float $pageRate, int $pages, int $spacingFactor): float
    {
        $mode = $who === 'writer' ? $payout->writerPageMode : $payout->editorPageMode;
        $value = $who === 'writer' ? $payout->writerPageValue : $payout->editorPageValue;

        if ($mode === 'fixed') {
            $rate = self::money($value * $spacingFactor);
        } else {
            $rate = self::money($pageRate * $value / 100);
        }

        return self::money($pages * $rate);
    }

    /**
     * Percentage writer/editor pay is a share of the customer subtotal.
     * The current article code multiplies that share by the word count a
     * second time. That figure is not used here, because it would overstate
     * what a writer is owed.
     */
    private function wordPayout(string $mode, float $value, int $words, float $base): float
    {
        if ($mode === 'fixed') {
            return self::money($words * $value);
        }

        return self::money($base * $value / 100);
    }

    /**
     * @param  list<Line>  $lines
     * @param  list<string>  $notes
     */
    private function finish(
        array $lines,
        float $base,
        float $discount,
        array $notes,
        bool $progressiveAvailable,
        bool $progressiveForced,
        ?string $progressiveReason,
        float $progressivePrice,
    ): Quote {
        $total = 0.0;
        $extras = 0.0;
        $writer = 0.0;
        $editor = 0.0;

        foreach ($lines as $line) {
            $total = self::money($total + $line->amount);
            $writer = self::money($writer + $line->writerAmount);
            $editor = self::money($editor + $line->editorAmount);
            if (! in_array($line->key, ['pages', 'slides', 'charts', 'words', 'item', 'discount'], true)) {
                $extras = self::money($extras + $line->amount);
            }
        }

        return new Quote(
            base: $base,
            extras: $extras,
            discount: $discount,
            total: $total,
            writerPay: $writer,
            editorPay: $editor,
            lines: $lines,
            notes: $notes,
            progressiveAvailable: $progressiveAvailable,
            progressiveForced: $progressiveForced,
            progressiveReason: $progressiveReason,
            progressivePrice: $progressivePrice,
        );
    }
}
