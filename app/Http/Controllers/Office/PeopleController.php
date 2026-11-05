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

    public function viewAs(Request $request, Employee $employee): RedirectResponse
    {
        $staff = $this->actor($request);
        abort_unless((int) $staff->employee_group === Employee::GROUP_ADMIN, 403);
        abort_if((int) $employee->employee_group === Employee::GROUP_ADMIN, 422);
        session(['office.view_as' => $employee->employee_id]);
        $this->audit($staff, 'view_as.start', 'employee', (string) $employee->employee_id);

        return redirect()->route('office.home')->with('status', 'Viewing as '.$employee->getFilamentName().'. Writes are blocked.');
    }

    public function stopViewAs(Request $request): RedirectResponse
    {
        $this->audit($this->actor($request), 'view_as.stop', 'employee', (string) session('office.view_as'));
        session()->forget('office.view_as');

        return redirect()->route('office.home')->with('status', 'Back to your own account.');
    }

    public function applications(Request $request): View
    {
        $staff = $this->permit($request, 'people.manage');

        return view('office.applications', [
            'staff' => $staff,
            'applicants' => Employee::query()->where('employee_group', 5)->where('is_application_completed', 1)->orderByDesc('employee_id')->paginate(20),
        ]);
    }

    public function decide(Request $request, Employee $employee): RedirectResponse
    {
        $staff = $this->permit($request, 'people.manage');
        abort_unless((int) $employee->employee_group === 5, 404);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'writer_category' => ['nullable', 'integer'],
            'academic_level' => ['nullable', 'integer'],
        ]);
        if ($data['decision'] === 'approve') {
            $employee->employee_group = Employee::GROUP_WRITER;
            $employee->is_application_approved = 1;
            $employee->is_application_rejected = 0;
            $employee->is_started = 1;
            $employee->writer_category = (int) ($data['writer_category'] ?? 2);
            $employee->academic_level = (int) ($data['academic_level'] ?? 0);
            $employee->application_approved_at = now();
        } else {
            $employee->is_application_rejected = 1;
            $employee->is_application_approved = 0;
            $employee->application_rejected_at = now();
        }
        $employee->save();
        $this->audit($staff, 'application.'.$data['decision'], 'employee', (string) $employee->employee_id);
        if ($data['decision'] === 'approve' && $employee->email) {
            app(\App\Services\Desk\OutboundMail::class)->send(
                (string) $employee->email,
                'Your application was approved',
                'You can sign in',
                ['Your sample was accepted. Sign in with the email and password you used on the application.'],
                route('office.login'),
                'Sign in',
                'orders',
                true,
            );
        }

        return back()->with('status', $data['decision'] === 'approve' ? 'Applicant is now a writer.' : 'Application rejected.');
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
