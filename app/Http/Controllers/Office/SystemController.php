<?php

namespace App\Http\Controllers\Office;

use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Deadline;
use App\Models\Employee;
use App\Models\News;
use App\Models\OfficeSetting;
use App\Models\Order;
use App\Models\RolePermission;
use App\Services\Office\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemController extends OfficeController
{
    public function matrix(Request $request): View
    {
        $staff = $this->permit($request, 'roles.manage');
        $saved = RolePermission::query()->get()->groupBy('employee_group');

        return view('office.matrix', [
            'staff' => $staff,
            'catalog' => Permissions::CATALOG,
            'roles' => [
                Employee::GROUP_SUB_ADMIN => 'Sub-admin',
                Employee::GROUP_EDITOR => 'Editor',
                Employee::GROUP_WRITER => 'Writer',
            ],
            'saved' => $saved,
            'defaults' => Permissions::DEFAULTS,
        ]);
    }

    public function saveMatrix(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'roles.manage');
        $posted = $request->input('allow', []);
        foreach ([Employee::GROUP_SUB_ADMIN, Employee::GROUP_EDITOR, Employee::GROUP_WRITER] as $group) {
            RolePermission::query()->where('employee_group', $group)->delete();
            foreach (array_keys(Permissions::CATALOG) as $permission) {
                RolePermission::query()->create([
                    'employee_group' => $group,
                    'permission' => $permission,
                    'allowed' => ! empty($posted[$group][$permission]),
                ]);
            }
        }
        $this->audit($staff, 'roles.matrix');

        return back()->with('status', 'Permission matrix saved. Admin keeps every permission.');
    }

    public function settings(Request $request): View
    {
        $staff = $this->permit($request, 'settings.manage');

        return view('office.settings', [
            'staff' => $staff,
            'deadlines' => Deadline::query()->orderBy('hrs_customer')->limit(40)->get(),
            'coupons' => Coupon::query()->orderByDesc('coupon_id')->limit(20)->get(),
            'refundLimit' => OfficeSetting::get('subadmin_refund_limit', '150'),
            'sessionMinutes' => OfficeSetting::get('session_minutes', '30'),
            'gateways' => $this->gateways(),
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'settings.manage');
        $data = $request->validate([
            'subadmin_refund_limit' => ['required', 'numeric', 'min:0'],
            'session_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);
        foreach ($data as $key => $value) {
            OfficeSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->audit($staff, 'settings.save');

        return back()->with('status', 'Office settings saved. Gateway keys stay in the environment file.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'settings.manage');
        $sent = app(\App\Services\Desk\OutboundMail::class)->send(
            (string) $staff->email,
            'Test email from the office',
            'Test email',
            ['This is a test from the office settings. If you can read it, the mailer is reaching you.'],
            route('office.settings'),
            'Back to settings',
            'orders',
            true,
        );
        $this->audit($staff, 'mail.test', 'employee', (string) $staff->employee_id, ['sent' => $sent]);

        return back()->with('status', $sent ? 'Test email queued to '.$staff->email.'.' : 'Test email was not sent. Safe mode or the mailer stopped it. The attempt is in the mail log.');
    }

    public function reports(Request $request): View
    {
        $staff = $this->permit($request, 'reports.view');

        return view('office.reports', [
            'staff' => $staff,
            'revenue' => (float) Order::query()->where('is_paid', 1)->sum('total_paid'),
            'orders' => Order::query()->count(),
            'writerPay' => (float) Order::query()->sum('writer_pay'),
            'byStatus' => Order::query()->selectRaw('status_id, count(*) as total')->groupBy('status_id')->pluck('total', 'status_id'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $staff = $this->permit($request, 'reports.view');
        $this->audit($staff, 'reports.export');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['order_id', 'status_id', 'title', 'total', 'total_paid', 'writer_pay', 'date_add']);
            Order::query()->orderBy('order_id')->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, [
                        $order->order_id, $order->status_id, $order->title, $order->total, $order->total_paid, $order->writer_pay, $order->date_add,
                    ]);
                }
            });
            fclose($out);
        }, 'orders.csv', ['Content-Type' => 'text/csv']);
    }

    public function news(Request $request): View
    {
        $staff = $this->staff($request);
        abort_unless(\App\Services\Office\Access::allows($staff, 'news.view') || \App\Services\Office\Access::allows($staff, 'news.manage'), 403);

        return view('office.news', [
            'staff' => $staff,
            'items' => News::query()->orderByDesc('date_add')->paginate(20),
        ]);
    }

    public function storeNews(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'news.manage');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'recipient' => ['required', 'in:all,writers,editors'],
        ]);
        $item = new News;
        $item->title = $data['title'];
        $item->content = $data['content'];
        $item->recipient = $data['recipient'];
        $item->date_add = now();
        $item->date_upd = now();
        $item->save();
        $this->audit($staff, 'news.publish', 'news', (string) $item->news_id);

        return back()->with('status', 'Announcement published.');
    }

    public function activity(Request $request): View
    {
        $staff = $this->permit($request, 'audit.view');

        return view('office.activity', [
            'staff' => $staff,
            'logs' => AuditLog::query()->orderByDesc('id')->paginate(40),
        ]);
    }

    /**
     * @return list<array{name: string, configured: bool, hint: string}>
     */
    private function gateways(): array
    {
        $mask = fn (?string $value) => $value ? ('••••'.substr($value, -4)) : 'Not set';

        return [
            ['name' => 'M-Pesa', 'configured' => (bool) config('services.mpesa.consumer_key'), 'hint' => $mask(config('services.mpesa.consumer_key'))],
            ['name' => 'Stripe', 'configured' => (bool) config('services.stripe.secret'), 'hint' => $mask(config('services.stripe.secret'))],
            ['name' => 'PayPal', 'configured' => (bool) config('services.paypal.client_id'), 'hint' => $mask(config('services.paypal.client_id'))],
            ['name' => 'Pesapal', 'configured' => (bool) config('services.pesapal.consumer_key'), 'hint' => $mask(config('services.pesapal.consumer_key'))],
        ];
    }
}
