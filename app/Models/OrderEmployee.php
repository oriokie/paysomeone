<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEmployee extends LegacyModel
{
    protected $table = 'prox_order_employee';

    protected $primaryKey = 'order_employee_id';

    protected function casts(): array
    {
        return [
            'employee_pay' => 'float',
            'is_employee_paid' => 'boolean',
            'is_employee_assigned' => 'boolean',
            'is_completed' => 'boolean',
            'is_delivered' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
