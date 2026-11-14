<?php

namespace App\Services\Outbound;

use RuntimeException;

/**
 * Phase 2 will connect M-Pesa, PayPal, Stripe, and Pesapal here.
 * Safe mode refuses the call before any network request is made.
 */
class PaymentGateway
{
    public function charge(string $provider, float $amount, string $reference): void
    {
        if (config('legacy.safe_mode')) {
            throw new RuntimeException('Payments are disabled while SAFE_MODE is on.');
        }

        throw new RuntimeException('The '.$provider.' payment connection is not switched on yet.');
    }
}
