<?php

declare(strict_types=1);

namespace App\Services\Pricing;

final class Quote
{
    /**
     * @param  list<Line>  $lines
     * @param  list<string>  $notes
     */
    public function __construct(
        public readonly float $base,
        public readonly float $extras,
        public readonly float $discount,
        public readonly float $total,
        public readonly float $writerPay,
        public readonly float $editorPay,
        public readonly array $lines,
        public readonly array $notes,
        public readonly bool $progressiveAvailable,
        public readonly bool $progressiveForced,
        public readonly ?string $progressiveReason,
        public readonly float $progressivePrice,
    ) {}

    public function amountDue(): float
    {
        return max(0, $this->total);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'extras' => $this->extras,
            'discount' => $this->discount,
            'total' => $this->total,
            'amount_due' => $this->amountDue(),
            'currency' => 'USD',
            'writer_pay' => $this->writerPay,
            'editor_pay' => $this->editorPay,
            'lines' => array_map(fn (Line $line) => [
                'key' => $line->key,
                'label' => $line->label,
                'amount' => $line->amount,
            ], $this->lines),
            'notes' => $this->notes,
            'progressive' => [
                'available' => $this->progressiveAvailable,
                'forced' => $this->progressiveForced,
                'reason' => $this->progressiveReason,
                'amount' => $this->progressivePrice,
            ],
        ];
    }
}
