<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaPayout extends LegacyModel
{
    protected $table = 'prox_mpesa_payout';

    protected $primaryKey = 'mpesa_payout_id';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id', 'payment_request_id');
    }
}
