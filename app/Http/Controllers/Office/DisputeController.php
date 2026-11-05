<?php

namespace App\Http\Controllers\Office;

use App\Models\Customer;
use App\Models\Dispute;
use App\Services\Desk\Notifier;
use App\Services\Payments\PaymentRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends OfficeController
{
    public function index(Request $request): View
    {
        $staff = $this->permit($request, 'disputes.manage');

        return view('office.disputes', [
            'staff' => $staff,
            'disputes' => Dispute::query()->with(['order', 'customer'])->orderByDesc('id')->paginate(20),
        ]);
    }

    public function resolve(Request $request, Dispute $dispute, PaymentRecorder $recorder, Notifier $notifier): RedirectResponse
    {
        $staff = $this->permit($request, 'disputes.manage');
        abort_unless($dispute->status === 'open', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'resolution' => ['required', 'string', 'max:2000'],
        ]);
        $customer = Customer::query()->find($dispute->customer_id);
        if ($data['decision'] === 'approve' && $customer && ! config('legacy.read_only')) {
            $order = $dispute->order;
            $amount = $order ? min((float) $order->total_paid, (float) $order->total) : 0;
            if ($amount > 0) {
                $recorder->creditWallet($customer, $amount, 'refund', 'dispute-'.$dispute->id, $order->currency_id ?? null);
            }
        }
        $dispute->status = $data['decision'] === 'approve' ? 'approved' : 'rejected';
        $dispute->resolution = $data['resolution'];
        $dispute->staff_id = $staff->employee_id;
        $dispute->save();
        $this->audit($staff, 'dispute.'.$dispute->status, 'dispute', (string) $dispute->id);
        if ($customer && $customer->email && ! str_ends_with((string) $customer->email, '@removed.invalid')) {
            $notifier->email('dispute_update', (string) $customer->email, [
                'order_id' => $dispute->order_id,
                'status' => $dispute->status,
                'resolution' => $data['resolution'],
            ], 'Dispute update for order #'.$dispute->order_id, 'orders', true, route('desk.order', $dispute->order_id));
        }

        return back()->with('status', 'Dispute '.$dispute->status.'.');
    }
}
