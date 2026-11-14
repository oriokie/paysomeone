<?php

namespace App\Http\Controllers;

use App\Exceptions\LegacyWriteBlocked;
use App\Models\AccountRequest;
use App\Models\Customer;
use App\Models\Dispute;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\SavedBrief;
use App\Services\Desk\Notifier;
use App\Support\Departments;
use App\Support\ReceiptPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountExtrasController extends Controller
{
    public function preferences(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        NotificationPreference::query()->updateOrCreate(['email' => strtolower($customer->email)], [
            'orders' => $request->boolean('orders'),
            'messages' => $request->boolean('messages'),
            'payments' => $request->boolean('payments'),
        ]);

        return back()->with('status', 'Email preferences saved. Security messages and payment receipts still send.');
    }

    public function export(Request $request): Response
    {
        $customer = $request->user('customer');
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'orders' => Order::query()->where('customer_id', $customer->customer_id)->get(['order_id', 'title', 'status_id', 'total', 'total_paid', 'date_add']),
            'payments' => PaymentIntent::query()->where('customer_id', $customer->customer_id)->get(['reference', 'provider', 'amount', 'status', 'created_at']),
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="account-'.$customer->customer_id.'.json"',
        ]);
    }

    public function deletion(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $row = AccountRequest::query()->create([
            'customer_id' => $customer->customer_id,
            'type' => 'deletion',
            'status' => 'pending',
        ]);
        if (config('legacy.read_only')) {
            return back()->with('status', 'Deletion requested. The live customer record stays until writes are allowed.');
        }
        $customer->name = 'Deleted customer';
        $customer->email = 'deleted-'.$customer->customer_id.'@removed.invalid';
        $customer->phone = null;
        $customer->password = Hash::make(Str::random(32));
        $customer->save();
        $row->status = 'completed';
        $row->completed_at = now();
        $row->save();
        auth('customer')->logout();

        return redirect()->route('home')->with('status', 'The account details that identify you have been removed. Orders stay so the studio can keep its records.');
    }

    public function reorder(Request $request, Order $order): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        $brief = SavedBrief::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'title' => $order->title,
            'brief' => (string) $order->paper_details,
        ]);

        return redirect()->route('order.create', ['brief' => $brief->id])->with('status', 'The brief is ready to send again. Check the details before you place it.');
    }

    public function receipt(Request $request, Order $order): Response
    {
        $customer = $this->owns($request, $order);
        abort_unless((float) $order->total_paid > 0, 404);
        $pdf = ReceiptPdf::make([
            'Receipt',
            'Order #'.$order->order_id,
            (string) $order->title,
            'Paid by '.$customer->name,
            'Amount $'.number_format((float) $order->total_paid, 2),
            'Method '.(string) ($order->payment_method ?: 'Recorded'),
            'Date '.($order->paid_at ? $order->paid_at->timezone(config('app.timezone'))->format('j M Y') : now()->format('j M Y')),
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-'.$order->order_id.'.pdf"',
        ]);
    }

    public function dispute(Request $request, Order $order, Notifier $notifier): RedirectResponse
    {
        $customer = $this->owns($request, $order);
        abort_unless((float) $order->total_paid > 0, 422);
        $open = Dispute::query()->where('order_id', $order->order_id)->where('status', 'open')->exists();
        if ($open) {
            return back()->withErrors(['reason' => 'This order already has an open dispute.']);
        }
        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:5000']]);
        $dispute = Dispute::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'reason' => $data['reason'],
            'status' => 'open',
        ]);
        if (! config('legacy.read_only')) {
            try {
                $order->status_id = Order::DISPUTE;
                $order->date_upd = now();
                $order->save();
            } catch (LegacyWriteBlocked) {
                // The dispute row is still kept.
            }
        }
        $notifier->staff('order', 'dispute', (string) $order->order_id, 'Dispute opened on order #'.$order->order_id, (int) $customer->customer_id);
        $notifier->email('dispute_opened', (string) $customer->email, [
            'order_id' => $order->order_id,
        ], 'We received your dispute for order #'.$order->order_id, 'orders', true, route('desk.order', $order));

        return back()->with('status', 'Dispute #'.$dispute->id.' is open. A manager will reply.');
    }

    public function markNotifications(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        Notification::query()
            ->where('to_department', (string) Departments::CUSTOMER)
            ->where('to_user_id', $customer->customer_id)
            ->where('is_seen', 0)
            ->update(['is_seen' => 1, 'date_upd' => now()]);

        return back()->with('status', 'Notifications marked as seen.');
    }

    private function owns(Request $request, Order $order): Customer
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        abort_unless((int) $order->customer_id === (int) $customer->customer_id, 403);

        return $customer;
    }
}
