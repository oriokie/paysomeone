<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewaySetting extends Model
{
    protected $table = 'app_gateway_settings';

    protected $primaryKey = 'gateway';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'test_mode' => 'boolean',
            'fee_percent' => 'float',
            'fee_fixed' => 'float',
        ];
    }
}
