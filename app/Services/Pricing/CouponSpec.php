<?php

declare(strict_types=1);

namespace App\Services\Pricing;

final class CouponSpec
{
    public const PERCENT = 1;

    public const AMOUNT = 3;

    public const FREE_UNIT = 5;

    public const WRITER_CATEGORY = 7;

    public const HALF_CUT_DEADLINE = 8;

    public const PRICE_PER_PAGE = 9;

    public function __construct(
        public readonly int $type,
        public readonly float $value = 0,
        public readonly int $quantity = 0,
        public readonly ?int $serviceTypeId = null,
        public readonly ?int $writerCategoryId = null,
    ) {}
}
