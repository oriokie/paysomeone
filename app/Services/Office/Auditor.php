<?php

namespace App\Services\Office;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class Auditor
{
    public static function write(?Employee $employee, string $action, ?string $type = null, ?string $id = null, array $meta = [], ?Request $request = null): void
    {
        $request ??= request();
        AuditLog::query()->create([
            'employee_id' => $employee?->employee_id,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255),
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
