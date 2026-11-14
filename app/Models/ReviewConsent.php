<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewConsent extends Model
{
    protected $table = 'app_review_consents';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['consented' => 'boolean'];
    }
}
