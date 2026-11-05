<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends LegacyModel
{
    protected $table = 'prox_academic_level';

    protected $primaryKey = 'academic_level_id';

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class, 'academic_level_id', 'academic_level_id');
    }
}
