<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends LegacyModel
{
    protected $table = 'prox_order_file';

    protected $primaryKey = 'file_id';

    protected function casts(): array
    {
        return [
            'is_final_file' => 'boolean',
            'approved' => 'boolean',
            'downloaded' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
