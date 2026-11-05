<?php

namespace App\Http\Controllers\Office;

use App\Models\CreditFine;
use App\Models\FineType;
use App\Models\MpesaPayout;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Services\Office\Access;
use App\Services\Payments\MpesaB2c;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MoneyController extends OfficeController
{
    public function earnings(Request $request): View
    {
        $staff = $this->staff($request);
        abort_unless(Access::allows($staff, 'payouts.request') || Access::allows($staff, 'payouts.approve'), 403);
        $writerId = Access::allows($staff, 'payouts.approve') && $request->filled('writer')
            ? (int) $request->query('writer')
            : (int) $staff->employee_id;

        $orders = Order::query()->where('writer_id', $writerId)->where('status_id', Order::FINISHED)->orderByDesc('order_id')->limit(50)->get();
        $earned = (float) $orders->sum('writer_pay');
        $fines = (float) CreditFine::query()->where('employee_id', $writerId)->where('type', 'fine')->sum('amount');
        $credits = (float) CreditFine::query()->where('employee_id', $writerId)->where('type', 'credit')->sum('amount');

        $approver = Access::allows($staff, 'payouts.approve');
        $requests = PaymentRequest::query()
            ->when(! $approver || $request->filled('writer'), fn ($query) => $query->where('user_id', $writerId))
            ->orderByDesc('payment_request_id')
            ->limit(30)
            ->get();

        return view('office.earnings', [
            'staff' => $staff,
            'orders' => $orders,
            'earned' => $earned,
            'fines' => $fines,
            'credits' => $credits,
            'requests' => $requests,
            'writers' => $approver
                ? \App\Models\Employee::query()->whereIn('employee_id', $requests->pluck('user_id'))->get()->keyBy('employee_id')
                : collect(),
            'safeMode' => (bool) config('legacy.safe_mode'),
        ]);
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'payouts.request');
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);
        $payout = new PaymentRequest;
        $payout->user_id = $staff->employee_id;
        $payout->total_pay = $data['amount'];
        $payout->total_done_pay = $data['amount'];
        $payout->status = 0;
        $payout->date_add = now();
        $payout->date_upd = now();
        $payout->save();

        $phone = preg_replace('/\D+/', '', $data['phone']) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '254'.substr($phone, 1);
        }
        $mpesa = new MpesaPayout;
        $mpesa->payment_request_id = $payout->payment_request_id;
        $mpesa->amount = $data['amount'];
        $mpesa->phone = $phone;
        $mpesa->is_paid = 0;
        $mpesa->transaction_id = config('legacy.safe_mode')
            ? 'safe-mode: not sent to M-Pesa'
            : 'queued';
        $mpesa->date_add = now();
        $mpesa->date_upd = now();
        $mpesa->save();
        $this->audit($staff, 'payout.request', 'payment_request', (string) $payout->payment_request_id, [
            'safe_mode' => (bool) config('legacy.safe_mode'),
            'amount' => $data['amount'],
        ]);

        $message = config('legacy.safe_mode')
            ? 'Payout request recorded. Safe mode is on, so nothing was sent to M-Pesa.'
            : 'Payout request recorded. An admin still has to approve the batch before money moves.';

        return back()->with('status', $message);
    }

    public function approve(Request $request, PaymentRequest $payout): RedirectResponse
    {
        $staff = $this->permit($request, 'payouts.approve');
        $row = MpesaPayout::query()->where('payment_request_id', $payout->payment_request_id)->orderByDesc('mpesa_payout_id')->first();
        $result = app(MpesaB2c::class)->send($payout, (string) ($row->phone ?? ''), (float) $payout->total_pay);
        if (! $result['ok']) {
            $this->audit($staff, 'payout.blocked', 'payment_request', (string) $payout->payment_request_id, $result);

            return back()->withErrors(['legacy' => $result['message']]);
        }
        if ($row && $result['conversation']) {
            $row->transaction_id = $result['conversation'];
            $row->date_upd = now();
            $row->save();
        }
        $this->audit($staff, 'payout.sent', 'payment_request', (string) $payout->payment_request_id, $result);

        return back()->with('status', $result['message']);
    }

    public function fines(Request $request): \Illuminate\View\View
    {
        $staff = $this->permit($request, 'fines.manage');

        return view('office.fines', [
            'staff' => $staff,
            'types' => FineType::query()->orderBy('name')->get(),
            'writers' => \App\Models\Employee::query()->where('employee_group', \App\Models\Employee::GROUP_WRITER)->orderBy('first_name')->limit(100)->get(),
        ]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'fines.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:155'],
            'type' => ['required', 'in:percentage,amount'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);
        $type = new FineType;
        $type->name = $data['name'];
        $type->type = $data['type'];
        $type->value = $data['value'];
        $type->date_add = now();
        $type->date_upd = now();
        $type->save();
        $this->audit($staff, 'fine_type.create', 'fine_type', (string) $type->fine_type_id);

        return back()->with('status', 'Fine type saved.');
    }

    public function applyType(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'fines.manage');
        $data = $request->validate([
            'fine_type_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
            'order_id' => ['nullable', 'integer'],
            'base' => ['nullable', 'numeric', 'min:0'],
        ]);
        $type = FineType::query()->findOrFail($data['fine_type_id']);
        $amount = $type->type === 'percentage'
            ? round(((float) ($data['base'] ?? 0) * (float) $type->value) / 100, 2)
            : (float) $type->value;
        $row = new CreditFine;
        $row->type = 'fine';
        $row->employee_id = $data['employee_id'];
        $row->order_id = ($data['order_id'] ?? null) ?: null;
        $row->amount = $amount;
        $row->description = $type->name;
        $row->date_add = now();
        $row->date_upd = now();
        $row->save();
        $this->audit($staff, 'fine.apply', 'credit_fine', (string) $row->credit_fine_id, ['fine_type_id' => $type->fine_type_id]);

        return back()->with('status', 'Fine applied: '.$type->name.' ($'.number_format($amount, 2).').');
    }

    public function appeal(Request $request, CreditFine $fine): RedirectResponse
    {
        $staff = $this->staff($request);
        abort_unless((int) $fine->employee_id === (int) $staff->employee_id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $fine->requested = 1;
        $fine->requested_at = now();
        $fine->description = trim($fine->description."\nAppeal: ".$data['reason']);
        $fine->date_upd = now();
        $fine->save();
        $this->audit($staff, 'fine.appeal', 'credit_fine', (string) $fine->credit_fine_id);

        return back()->with('status', 'Appeal recorded. An admin can resolve it.');
    }

    public function fine(Request $request): RedirectResponse
    {
        $staff = $this->permit($request, 'fines.manage');
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'order_id' => ['nullable', 'integer'],
            'type' => ['required', 'in:fine,credit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:2000'],
        ]);
        $row = new CreditFine;
        $row->type = $data['type'];
        $row->employee_id = $data['employee_id'];
        $row->order_id = ($data['order_id'] ?? null) ?: null;
        $row->amount = $data['amount'];
        $row->description = $data['description'];
        $row->date_add = now();
        $row->date_upd = now();
        $row->save();
        $this->audit($staff, 'fine.create', 'credit_fine', (string) $row->credit_fine_id, $data);

        return back()->with('status', ucfirst($data['type']).' recorded.');
    }
}
