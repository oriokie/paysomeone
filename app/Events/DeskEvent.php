<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phase 3 listens for these. The customer desk fires them after a write succeeds.
 * Nothing here calls the staff office, and nothing here can sign a staff member in.
 */
class DeskEvent
{
    use Dispatchable;

    public function __construct(
        public readonly string $name,
        public readonly int $orderId,
        public readonly array $context = [],
    ) {}
}
