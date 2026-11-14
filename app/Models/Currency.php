<?php

namespace App\Models;

class Currency extends LegacyModel
{
    protected $table = 'prox_currency';

    protected $primaryKey = 'currency_id';

    protected function casts(): array
    {
        return [
            'conversion_rate' => 'float',
            'active' => 'boolean',
        ];
    }
}
