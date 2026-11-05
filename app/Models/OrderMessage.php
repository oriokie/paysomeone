<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends LegacyModel
{
    protected $table = 'prox_order_message';

    protected $primaryKey = 'message_id';

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'is_viewed' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
