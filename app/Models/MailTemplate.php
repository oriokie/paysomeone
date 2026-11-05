<?php

namespace App\Models;

class MailTemplate extends LegacyModel
{
    protected $table = 'prox_mail_template';

    protected $primaryKey = 'mail_template_id';

    protected function casts(): array
    {
        return [
            'plaintext' => 'boolean',
            'is_disabled' => 'boolean',
        ];
    }
}
