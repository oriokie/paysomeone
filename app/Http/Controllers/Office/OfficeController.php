<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Office\Access;
use App\Services\Office\Auditor;
use Illuminate\Http\Request;

abstract class OfficeController extends Controller
{
    protected function actor(Request $request): Employee
    {
        /** @var Employee $staff */
        $staff = $request->user('staff');

        return $staff;
    }

    protected function staff(Request $request): Employee
    {
        $actor = $this->actor($request);
        $viewAs = (int) session('office.view_as', 0);
        if ($viewAs > 0 && (int) $actor->employee_group === Employee::GROUP_ADMIN) {
            return Employee::query()->find($viewAs) ?? $actor;
        }

        return $actor;
    }

    protected function permit(Request $request, string $permission): Employee
    {
        $staff = $this->staff($request);
        abort_unless(Access::allows($staff, $permission), 403);

        return $staff;
    }

    protected function audit(Employee $staff, string $action, ?string $type = null, ?string $id = null, array $meta = []): void
    {
        Auditor::write($this->actor(request()), $action, $type, $id, $meta);
    }
}
