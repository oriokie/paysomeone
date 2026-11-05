<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTip extends LegacyModel
{
    protected $table = 'prox_order_tip';

    protected $primaryKey = 'order_tip_id';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'writer_tip_amount' => 'float',
            'editor_tip_amount' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
