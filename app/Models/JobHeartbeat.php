<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobHeartbeat extends Model
{
    public $timestamps = false;

    protected $table = 'app_job_heartbeats';

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['ran_at' => 'datetime'];
    }
}
