<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'app_notification_preferences';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'orders' => 'boolean',
            'messages' => 'boolean',
            'payments' => 'boolean',
        ];
    }

    public static function allows(string $email, string $category): bool
    {
        $row = static::query()->where('email', strtolower($email))->first();
        if (! $row) {
            return true;
        }

        return (bool) match ($category) {
            'messages' => $row->messages,
            'payments' => $row->payments,
            default => $row->orders,
        };
    }
}
