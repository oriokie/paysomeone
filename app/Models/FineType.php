<?php

namespace App\Models;

class FineType extends LegacyModel
{
    protected $table = 'prox_fine_type';

    protected $primaryKey = 'fine_type_id';

    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
