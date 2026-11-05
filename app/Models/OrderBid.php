<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderBid extends LegacyModel
{
    protected $table = 'prox_order_bid';

    protected $primaryKey = 'bid_id';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'is_assigned' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function writer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'writer_id', 'employee_id');
    }
}
