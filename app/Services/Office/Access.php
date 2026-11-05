<?php

namespace App\Services\Office;

use App\Models\Employee;
use App\Models\RolePermission;

class Access
{
    public static function allows(Employee $employee, string $permission): bool
    {
        if ((int) $employee->employee_group === Employee::GROUP_ADMIN) {
            return true;
        }

        if ((int) $employee->is_banned === 1 || ! $employee->is_started) {
            return false;
        }

        $group = (int) $employee->employee_group;
        $hasMatrix = RolePermission::query()->where('employee_group', $group)->exists();
        if ($hasMatrix) {
            return RolePermission::query()
                ->where('employee_group', $group)
                ->where('permission', $permission)
                ->where('allowed', true)
                ->exists();
        }

        return in_array($permission, Permissions::DEFAULTS[$group] ?? [], true);
    }

    public static function seesPii(Employee $employee): bool
    {
        return self::allows($employee, 'customers.pii');
    }
}
