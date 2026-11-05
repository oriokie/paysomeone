<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPricing extends LegacyModel
{
    protected $table = 'prox_item_pricing';

    protected $primaryKey = 'item_pricing_id';

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_calculations' => 'boolean',
            'is_programming' => 'boolean',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class, 'discipline_id', 'discipline_id');
    }
}
