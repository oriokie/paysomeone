<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    public $timestamps = false;

    protected $table = 'app_mail_logs';

    protected $guarded = [];
}
