<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePool extends LegacyModel
{
    protected $table = 'prox_employee_pool';

    protected $primaryKey = 'employee_pool_id';

    protected function casts(): array
    {
        return [
            'price_per_page' => 'float',
            'is_default' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'employee_pool_id', 'employee_pool_id');
    }
}
