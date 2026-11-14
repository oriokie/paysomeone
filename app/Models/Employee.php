<?php

namespace App\Models;

use App\Models\Concerns\GuardsLegacyWrites;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable implements FilamentUser, HasName
{
    use GuardsLegacyWrites;

    public const GROUP_ADMIN = 1;

    public const GROUP_EDITOR = 2;

    public const GROUP_WRITER = 3;

    public const GROUP_SUB_ADMIN = 4;

    public const CATEGORY_BEST = 2;

    public const CATEGORY_ESL = 3;

    public const CATEGORY_ENL = 4;

    protected $table = 'prox_employee';

    protected $primaryKey = 'employee_id';

    protected $guarded = [];

    public $timestamps = false;

    protected $hidden = [
        'password',
        'reset_password_token',
        'secure_key',
        'two_factor_key',
        'two_factor_gsecret',
    ];

    protected function casts(): array
    {
        return [
            'is_banned' => 'boolean',
            'is_started' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'employee_group' => 'integer',
            'reg_date' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getFilamentName(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : (string) $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_banned) {
            return false;
        }

        return in_array((int) $this->employee_group, [self::GROUP_ADMIN, self::GROUP_SUB_ADMIN], true);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(EmployeePool::class, 'employee_pool_id', 'employee_pool_id');
    }

    public function writingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'writer_id', 'employee_id');
    }

    public function editingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'editor_id', 'employee_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(OrderBid::class, 'writer_id', 'employee_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'user_id', 'employee_id');
    }

    public function fines(): HasMany
    {
        return $this->hasMany(CreditFine::class, 'employee_id', 'employee_id');
    }
}
