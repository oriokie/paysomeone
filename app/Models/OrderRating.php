<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRating extends LegacyModel
{
    protected $table = 'prox_order_rating';

    protected $primaryKey = 'rating_id';

    protected function casts(): array
    {
        return [
            'paper_rating' => 'integer',
            'service_rating' => 'integer',
            'paper_on_time' => 'boolean',
            'date_add' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id', 'customer_id');
    }
}
