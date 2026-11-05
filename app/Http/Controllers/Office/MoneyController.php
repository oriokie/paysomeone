<?php

namespace App\Http\Controllers\Office;

use App\Models\CreditFine;
use App\Models\MpesaPayout;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestOrder;
use App\Services\Office\Access;
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

        return view('office.earnings', [
            'staff' => $staff,
            'orders' => $orders,
            'earned' => $earned,
            'fines' => $fines,
            'credits' => $credits,
            'requests' => PaymentRequest::query()->where('user_id', $writerId)->orderByDesc('payment_request_id')->limit(20)->get(),
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
        if (config('legacy.safe_mode')) {
            $this->audit($staff, 'payout.blocked', 'payment_request', (string) $payout->payment_request_id);

            return back()->withErrors(['legacy' => 'Safe mode is on. The payout stays unpaid and M-Pesa was not contacted.']);
        }
        $payout->status = 1;
        $payout->paid_at = now();
        $payout->date_upd = now();
        $payout->save();
        $this->audit($staff, 'payout.approve', 'payment_request', (string) $payout->payment_request_id);

        return back()->with('status', 'Payout marked paid in the office. Live B2C still needs the gateway keys.');
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
        $row->order_id = $data['order_id'] ?: null;
        $row->amount = $data['amount'];
        $row->description = $data['description'];
        $row->date_add = now();
        $row->date_upd = now();
        $row->save();
        $this->audit($staff, 'fine.create', 'credit_fine', (string) $row->credit_fine_id, $data);

        return back()->with('status', ucfirst($data['type']).' recorded.');
    }
}
