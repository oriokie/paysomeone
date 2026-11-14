<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'app_role_permissions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }
}
