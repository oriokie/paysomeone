<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaypalPayment extends LegacyModel
{
    protected $table = 'prox_paypal_payment';

    protected $primaryKey = 'payment_id';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'node_id', 'order_id');
    }
}
