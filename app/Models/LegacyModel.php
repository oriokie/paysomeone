<?php

namespace App\Models;

use App\Models\Concerns\GuardsLegacyWrites;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for tables that already exist in the live MariaDB database.
 * There is no migration for these tables. Do not add one.
 */
abstract class LegacyModel extends Model
{
    use GuardsLegacyWrites;

    protected $guarded = [];

    public $timestamps = true;

    const CREATED_AT = 'date_add';

    const UPDATED_AT = 'date_upd';

    protected $keyType = 'int';
}
