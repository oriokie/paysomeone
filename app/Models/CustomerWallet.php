<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerWallet extends LegacyModel
{
    protected $table = 'prox_customer_wallet';

    protected $primaryKey = 'wallet_id';

    protected function casts(): array
    {
        return [
            'wallet_balance' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }
}
