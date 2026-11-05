<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Office\Access;
use App\Services\Office\Auditor;
use Illuminate\Http\Request;

abstract class OfficeController extends Controller
{
    protected function staff(Request $request): Employee
    {
        /** @var Employee $staff */
        $staff = $request->user('staff');

        return $staff;
    }

    protected function permit(Request $request, string $permission): Employee
    {
        $staff = $this->staff($request);
        abort_unless(Access::allows($staff, $permission), 403);

        return $staff;
    }

    protected function audit(Employee $staff, string $action, ?string $type = null, ?string $id = null, array $meta = []): void
    {
        Auditor::write($staff, $action, $type, $id, $meta);
    }
}
