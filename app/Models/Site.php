<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends LegacyModel
{
    protected $table = 'prox_site';

    protected $primaryKey = 'site_id';

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'site_id', 'site_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'site_id', 'site_id');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(Configuration::class, 'site_id', 'site_id');
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class, 'site_id', 'site_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'site_id', 'site_id');
    }
}
