<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deadline extends LegacyModel
{
    protected $table = 'prox_deadline';

    protected $primaryKey = 'deadline_id';

    protected function casts(): array
    {
        return [
            'price_per_page' => 'float',
            'price_modifier' => 'float',
            'hrs_customer' => 'integer',
            'hrs_writer' => 'integer',
            'min_pages' => 'integer',
            'is_academic_writing' => 'boolean',
            'is_calculations' => 'boolean',
            'is_programming' => 'boolean',
            'is_article_writing' => 'boolean',
            'is_resume' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id', 'academic_level_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'tariff_id', 'deadline_id');
    }
}
