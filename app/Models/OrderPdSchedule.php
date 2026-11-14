<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPdSchedule extends LegacyModel
{
    protected $table = 'prox_order_pd_schedule';

    protected $primaryKey = 'schedule_id';

    protected function casts(): array
    {
        return [
            'writer_pay' => 'float',
            'client_pay' => 'float',
            'is_paid' => 'boolean',
            'is_completed' => 'boolean',
            'client_deadline' => 'datetime',
            'writer_deadline' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
