<?php

namespace App\Models;

class PaperType extends LegacyModel
{
    protected $table = 'prox_paper_type';

    protected $primaryKey = 'paper_type_id';

    protected function casts(): array
    {
        return [
            'is_academic_writing' => 'boolean',
            'is_article_writing' => 'boolean',
        ];
    }
}
