<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalService extends LegacyModel
{
    protected $table = 'prox_additional_service';

    protected $primaryKey = 'additional_service_id';

    protected function casts(): array
    {
        return [
            'total' => 'float',
            'is_paid' => 'boolean',
            'writer_samples' => 'boolean',
            'used_sources' => 'boolean',
            'expert_proofreading' => 'boolean',
            'progressive_delivery' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
