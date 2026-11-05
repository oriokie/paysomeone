<?php

namespace App\Http\Controllers\Office;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeePool;
use App\Services\Office\Access;
use App\Services\Office\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeopleController extends OfficeController
{
    public function index(Request $request): View
    {
        $staff = $this->permit($request, 'people.manage');

        return view('office.people', [
            'staff' => $staff,
            'people' => Employee::query()->orderBy('employee_group')->orderBy('first_name')->paginate(30),
            'pools' => EmployeePool::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $staff = $this->permit($request, 'people.manage');
        abort_if((int) $employee->employee_group === Employee::GROUP_ADMIN && (int) $staff->employee_group !== Employee::GROUP_ADMIN, 403);
        $data = $request->validate([
            'is_banned' => ['nullable', 'boolean'],
            'writer_category' => ['nullable', 'integer'],
            'employee_pool_id' => ['nullable', 'integer'],
        ]);
        if (array_key_exists('is_banned', $data)) {
            $employee->is_banned = (bool) $request->boolean('is_banned');
        }
        if (isset($data['writer_category'])) {
            $employee->writer_category = (int) $data['writer_category'];
        }
        if (isset($data['employee_pool_id'])) {
            $employee->employee_pool_id = $data['employee_pool_id'] ?: null;
        }
        $employee->save();
        $this->audit($staff, 'staff.update', 'employee', (string) $employee->employee_id, $data);

        return back()->with('status', 'Staff record updated. This is not a sign-in as that person.');
    }

    public function customers(Request $request): View
    {
        $staff = $this->permit($request, 'customers.view');
        $customers = Customer::query()
            ->when($request->filled('q'), fn ($q) => $q->where('email', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('customer_id')
            ->paginate(30);

        return view('office.customers', [
            'staff' => $staff,
            'customers' => $customers,
            'reveal' => Access::seesPii($staff),
        ]);
    }
}
