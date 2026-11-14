<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDevice extends Model
{
    public $timestamps = false;

    protected $table = 'app_staff_devices';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
