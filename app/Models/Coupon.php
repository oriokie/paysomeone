<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends LegacyModel
{
    public const PERCENT = 1;

    public const AMOUNT = 3;

    public const FREE_UNIT = 5;

    public const WRITER_CATEGORY = 7;

    public const HALF_CUT_DEADLINE = 8;

    public const PRICE_PER_PAGE = 9;

    protected $table = 'prox_coupon';

    protected $primaryKey = 'coupon_id';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'one_time' => 'boolean',
            'expiry' => 'datetime',
            'coupon_value' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function isUsable(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->expiry && $this->expiry->isPast()) {
            return false;
        }

        if ($this->one_time && $this->order_id) {
            return false;
        }

        return true;
    }
}
