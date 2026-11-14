<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditFine extends LegacyModel
{
    protected $table = 'prox_credit_fine';

    protected $primaryKey = 'credit_fine_id';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'resolved' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
