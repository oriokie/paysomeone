<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends LegacyModel
{
    protected $table = 'prox_payment';

    protected $primaryKey = 'payment_id';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'conversion_rate' => 'float',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'currency_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'node_id', 'order_id');
    }
}
