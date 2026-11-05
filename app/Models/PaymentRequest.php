<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends LegacyModel
{
    protected $table = 'prox_payment_request';

    protected $primaryKey = 'payment_request_id';

    protected function casts(): array
    {
        return [
            'total_pay' => 'float',
            'total_credit' => 'float',
            'total_fine' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'user_id', 'employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PaymentRequestOrder::class, 'payment_request_id', 'payment_request_id');
    }

    public function mpesaPayouts(): HasMany
    {
        return $this->hasMany(MpesaPayout::class, 'payment_request_id', 'payment_request_id');
    }
}
