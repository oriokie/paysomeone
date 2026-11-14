<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends LegacyModel
{
    protected $table = 'prox_referral';

    protected $primaryKey = 'referral_id';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'referrer_used' => 'boolean',
            'referree_used' => 'boolean',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_id', 'customer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referree_id', 'customer_id');
    }
}
