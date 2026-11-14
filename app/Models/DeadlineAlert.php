<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeadlineAlert extends Model
{
    public $timestamps = false;

    protected $table = 'app_deadline_alerts';

    protected $guarded = [];
}
