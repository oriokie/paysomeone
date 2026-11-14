<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedView extends Model
{
    protected $table = 'app_saved_views';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
