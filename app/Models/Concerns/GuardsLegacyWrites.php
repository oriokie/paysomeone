<?php

namespace App\Models\Concerns;

use App\Exceptions\LegacyWriteBlocked;

trait GuardsLegacyWrites
{
    public static function bootGuardsLegacyWrites(): void
    {
        $guard = function ($model): void {
            $model->blockLegacyWrite();
        };

        static::saving($guard);
        static::deleting($guard);
    }

    public function blockLegacyWrite(): void
    {
        if (! config('legacy.read_only')) {
            return;
        }

        $prefix = (string) config('legacy.table_prefix', 'prox_');

        if (str_starts_with($this->getTable(), $prefix)) {
            throw new LegacyWriteBlocked($this->getTable());
        }
    }
}
