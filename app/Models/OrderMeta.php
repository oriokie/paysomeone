<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMeta extends LegacyModel
{
    protected $table = 'prox_order_meta';

    protected $primaryKey = 'meta_id';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
