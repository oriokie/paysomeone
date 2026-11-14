<?php

namespace Tests\Unit;

use App\Services\Pricing\CouponSpec;
use App\Services\Pricing\PayoutRates;
use App\Services\Pricing\PriceCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator;
    }

    #[Test]
    public function double_spaced_pages_use_the_deadline_rate(): void
    {
        $quote = $this->calculator->quotePages(pricePerPage: 10, hours: 72, pages: 2);

        $this->assertSame(20.0, $quote->total);
        $this->assertSame(20.0, $quote->amountDue());
    }

    #[Test]
    public function single_spacing_doubles_the_page_rate_but_not_the_slide_rate(): void
    {
        $pages = $this->calculator->quotePages(pricePerPage: 10, hours: 72, pages: 2, spacing: 'single');
        $slides = $this->calculator->quotePages(pricePerPage: 10, hours: 72, pages: 0, slides: 1, spacing: 'single');

        $this->assertSame(40.0, $pages->total);
        $this->assertSame(5.0, $slides->total);
    }

    #[Test]
    public function extras_follow_the_published_percentages_and_flat_fees(): void
    {
        $proof = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, proofreading: true);
        $sourcesSmall = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, sources: true);
        $sourcesLarge = $this->calculator->quotePages(pricePerPage: 50, hours: 120, pages: 4, sources: true);
        $complex = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, complex: true);
        $outline = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, outline: true);
        $preferred = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, preferredWriter: true);
        $samples = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, samples: true);
        $vip = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, vip: true);
        $tier = $this->calculator->quotePages(pricePerPage: 50, hours: 72, pages: 2, writerPercent: 25, writerCategoryId: 3);

        $this->assertSame(125.0, $proof->total);
        $this->assertSame(114.95, $sourcesSmall->total);
        $this->assertSame(220.0, $sourcesLarge->total);
        $this->assertSame(120.0, $complex->total);
        $this->assertSame(125.0, $outline->total);
        $this->assertSame(110.0, $preferred->total);
        $this->assertSame(105.0, $samples->total);
        $this->assertSame(109.99, $vip->total);
        $this->assertSame(125.0, $tier->total);
    }

    #[Test]
    public function progressive_delivery_is_offered_forced_or_refused_by_size_and_deadline(): void
    {
        $tooSmall = $this->calculator->quotePages(pricePerPage: 50, hours: 48, pages: 2, progressive: true);
        $optional = $this->calculator->quotePages(pricePerPage: 50, hours: 120, pages: 5, progressive: true);
        $forced = $this->calculator->quotePages(pricePerPage: 50, hours: 168, pages: 12, progressive: false);

        $this->assertFalse($tooSmall->progressiveAvailable);
        $this->assertSame(100.0, $tooSmall->total);
        $this->assertSame(275.0, $optional->total);
        $this->assertTrue($forced->progressiveForced);
        $this->assertSame(660.0, $forced->total);
    }

    #[Test]
    public function coupons_change_the_rate_or_take_a_percent_or_an_amount(): void
    {
        $percent = $this->calculator->quotePages(
            pricePerPage: 50,
            hours: 72,
            pages: 2,
            coupon: new CouponSpec(CouponSpec::PERCENT, 10),
        );
        $amount = $this->calculator->quotePages(
            pricePerPage: 50,
            hours: 72,
            pages: 2,
            coupon: new CouponSpec(CouponSpec::AMOUNT, 15),
        );
        $rate = $this->calculator->quotePages(
            pricePerPage: 50,
            hours: 72,
            pages: 2,
            coupon: new CouponSpec(CouponSpec::PRICE_PER_PAGE, 8),
        );
        $untouched = $this->calculator->quotePages(
            pricePerPage: 10,
            hours: 72,
            pages: 2,
            coupon: new CouponSpec(CouponSpec::HALF_CUT_DEADLINE, 50),
        );

        $this->assertSame(90.0, $percent->total);
        $this->assertSame(85.0, $amount->total);
        $this->assertSame(16.0, $rate->total);
        $this->assertSame(20.0, $untouched->total);
    }

    #[Test]
    public function a_free_page_coupon_leaves_one_page_paid(): void
    {
        $quote = $this->calculator->quotePages(
            pricePerPage: 10,
            hours: 72,
            pages: 3,
            winbacks: [new CouponSpec(CouponSpec::FREE_UNIT, quantity: 5, serviceTypeId: PriceCalculator::PAGES)],
        );

        $this->assertSame(10.0, $quote->total);
    }

    #[Test]
    public function article_prices_are_per_word_and_the_percent_coupon_ignores_the_writer_tier(): void
    {
        $plain = $this->calculator->quoteWords(pricePerWord: 0.05, words: 1000, writerPercent: 25);
        $discounted = $this->calculator->quoteWords(
            pricePerWord: 0.05,
            words: 1000,
            writerPercent: 25,
            coupon: new CouponSpec(CouponSpec::PERCENT, 10),
        );

        $this->assertSame(62.5, $plain->total);
        $this->assertSame(57.5, $discounted->total);
    }

    #[Test]
    public function item_prices_use_the_deadline_modifier(): void
    {
        $quote = $this->calculator->quoteItem(80, 1.5, new CouponSpec(CouponSpec::AMOUNT, 20));

        $this->assertSame(100.0, $quote->total);
    }

    #[Test]
    public function writer_page_pay_is_a_share_of_the_page_rate(): void
    {
        $quote = $this->calculator->quotePages(
            pricePerPage: 10,
            hours: 72,
            pages: 2,
            payout: new PayoutRates(writerPageMode: 'percentage', writerPageValue: 40),
        );

        $this->assertSame(20.0, $quote->total);
        $this->assertSame(8.0, $quote->writerPay);
    }
}
