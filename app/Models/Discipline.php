<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends LegacyModel
{
    protected $table = 'prox_discipline';

    protected $primaryKey = 'discipline_id';

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'is_complex_assignment' => 'boolean',
            'is_academic_writing' => 'boolean',
            'is_calculations' => 'boolean',
            'is_programming' => 'boolean',
            'is_article_writing' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPricing::class, 'discipline_id', 'discipline_id');
    }
}
