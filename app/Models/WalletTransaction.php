<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends LegacyModel
{
    protected $table = 'prox_wallet_transaction';

    protected $primaryKey = 'wallet_transaction_id';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'currency_id');
    }
}
