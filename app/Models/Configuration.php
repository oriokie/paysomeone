<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuration extends LegacyModel
{
    protected $table = 'prox_configuration';

    protected $primaryKey = 'configuration_id';

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }

    public static function read(string $name, mixed $default = null, ?int $siteId = null): mixed
    {
        $siteId ??= (int) config('legacy.admin_site_id');

        try {
            $row = static::query()->where('name', $name)->where('site_id', $siteId)->first()
                ?? static::query()->where('name', $name)->first();
        } catch (\Throwable) {
            return $default;
        }

        if ($row === null || $row->value === null || $row->value === '') {
            return $default;
        }

        return $row->value;
    }
}
