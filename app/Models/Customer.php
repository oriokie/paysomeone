<?php

namespace App\Models;

use App\Models\Concerns\GuardsLegacyWrites;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use GuardsLegacyWrites, Notifiable;

    protected $table = 'prox_customer';

    protected $primaryKey = 'customer_id';

    protected $guarded = [];

    public $timestamps = false;

    protected $hidden = [
        'password',
        'new_password',
        'reset_password_token',
        'secure_key',
    ];

    protected function casts(): array
    {
        return [
            'policy_accepted' => 'boolean',
            'is_subscribed' => 'boolean',
            'is_test' => 'boolean',
            'is_banned' => 'boolean',
            'is_anonymous' => 'boolean',
            'total_spent' => 'float',
            'wallet_balance' => 'float',
            'affiliate_balance' => 'float',
            'reg_date' => 'datetime',
            'last_login' => 'datetime',
            'last_activity' => 'datetime',
            'last_passwd_gen' => 'datetime',
        ];
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(CustomerWallet::class, 'customer_id', 'customer_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'customer_id', 'customer_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(OrderRating::class, 'user_id', 'customer_id')
            ->where('user_type', 'customer');
    }
}
