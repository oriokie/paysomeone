<?php

declare(strict_types=1);

namespace App\Services\Pricing;

final class Line
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly float $amount,
        public readonly float $writerAmount = 0,
        public readonly float $editorAmount = 0,
    ) {}
}
