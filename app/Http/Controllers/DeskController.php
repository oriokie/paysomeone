<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\OrderMessage;
use App\Models\Referral;
use App\Models\WalletTransaction;
use App\Support\Departments;
use App\Support\OrderStatuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeskController extends Controller
{
    public function home(Request $request): View
    {
        $customer = $this->customer($request);
        $orders = Order::query()->where('customer_id', $customer->customer_id);

        $owned = Order::query()->where('customer_id', $customer->customer_id)->select('order_id');

        return view('desk.home', [
            'customer' => $customer,
            'orders' => (clone $orders)->forCustomerTab('recent')->orderByDesc('order_id')->limit(6)->get(),
            'unpaid' => (clone $orders)->forCustomerTab('unpaid')->count(),
            'open' => (clone $orders)->whereNotIn('status_id', [Order::FINISHED, Order::CANCELLED])->count(),
            'unpaidOrders' => (clone $orders)->forCustomerTab('unpaid')->orderByDesc('order_id')->limit(4)->get(),
            'ready' => (clone $orders)->where('status_id', Order::DELIVERED)->orderByDesc('order_id')->limit(4)->get(),
            'revisions' => (clone $orders)->where('status_id', Order::REVISION)->orderByDesc('order_id')->limit(4)->get(),
            'messages' => OrderMessage::query()->with('order')->whereIn('order_id', $owned)->where('is_deleted', 0)->orderByDesc('date_add')->limit(4)->get(),
            'unreadMessages' => $this->unreadMessages($customer),
            'alerts' => Notification::query()
                ->where('to_department', (string) Departments::CUSTOMER)
                ->where('to_user_id', $customer->customer_id)
                ->where('is_seen', 0)
                ->count(),
        ]);
    }

    public function messages(Request $request): View
    {
        $customer = $this->customer($request);
        $owned = Order::query()->where('customer_id', $customer->customer_id)->select('order_id');
        $messages = OrderMessage::query()
            ->with('order')
            ->whereIn('order_id', $owned)
            ->where('is_deleted', 0)
            ->orderByDesc('date_add')
            ->paginate(20);

        return view('desk.messages', [
            'messages' => $messages,
        ]);
    }

    public function orders(Request $request): View
    {
        $customer = $this->customer($request);
        $status = (string) $request->query('status', 'recent');
        if (! isset(OrderStatuses::TABS[$status])) {
            $status = 'recent';
        }

        $orders = Order::query()
            ->where('customer_id', $customer->customer_id)
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->forCustomerTab($status)
            ->orderByDesc('order_id')
            ->paginate(12)
            ->withQueryString();

        $counts = [];
        $base = Order::query()->where('customer_id', $customer->customer_id);
        foreach (array_keys(OrderStatuses::TABS) as $tab) {
            $counts[$tab] = (clone $base)->forCustomerTab($tab)->count();
        }

        return view('desk.orders', [
            'orders' => $orders,
            'status' => $status,
            'filters' => OrderStatuses::TABS,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->owns($request, $order);
        $order->load([
            'files', 'messages', 'revisions', 'ratings', 'tips', 'payments',
            'bids.writer', 'academicLevel', 'paperType', 'discipline', 'paperFormat',
            'englishType', 'targetAudience', 'writingTone', 'writingStyle', 'contentFeel',
            'deadline', 'writer',
        ]);

        return view('desk.order', [
            'order' => $order,
            'customer' => $this->customer($request),
            'readOnly' => (bool) config('legacy.read_only'),
            'safeMode' => (bool) config('legacy.safe_mode'),
        ]);
    }

    public function payments(Request $request): View
    {
        $customer = $this->customer($request);
        $perPage = in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10;
        $query = PaymentIntent::query()->where('customer_id', $customer->customer_id);
        if ($request->filled('provider')) {
            $query->where('provider', (string) $request->query('provider'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('q')) {
            $term = (string) $request->query('q');
            $query->where(function ($inner) use ($term) {
                $inner->where('reference', 'like', '%'.$term.'%')
                    ->orWhere('transaction_id', 'like', '%'.$term.'%')
                    ->orWhere('order_id', $term);
            });
        }

        return view('desk.payments', [
            'customer' => $customer,
            'payments' => $query->orderByDesc('id')->paginate($perPage)->withQueryString(),
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'provider' => (string) $request->query('provider', ''),
                'status' => (string) $request->query('status', ''),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function payment(Request $request, PaymentIntent $intent): View
    {
        $customer = $this->customer($request);
        abort_unless((int) $intent->customer_id === (int) $customer->customer_id, 403);

        return view('desk.payment', [
            'customer' => $customer,
            'payment' => $intent->load('order'),
        ]);
    }

    public function wallet(Request $request): View
    {
        $customer = $this->customer($request);

        return view('desk.wallet', [
            'customer' => $customer,
            'transactions' => WalletTransaction::query()->where('customer_id', $customer->customer_id)->orderByDesc('wallet_transaction_id')->paginate(10)->withQueryString(),
            'tipOrder' => Order::query()
                ->where('customer_id', $customer->customer_id)
                ->whereIn('status_id', [Order::DELIVERED, Order::FINISHED])
                ->orderByDesc('order_id')
                ->first(),
        ]);
    }

    public function referrals(Request $request): View
    {
        $customer = $this->customer($request);

        return view('desk.referrals', [
            'customer' => $customer,
            'referrals' => Referral::query()->where('referrer_id', $customer->customer_id)->orderByDesc('date_add')->get(),
            'link' => route('register', ['ref' => $customer->customer_id]),
        ]);
    }

    public function notifications(Request $request): View
    {
        $customer = $this->customer($request);
        $notes = Notification::query()
            ->where('to_department', (string) Departments::CUSTOMER)
            ->where('to_user_id', $customer->customer_id)
            ->orderByDesc('date_add')
            ->paginate(20);

        return view('desk.notifications', ['notes' => $notes]);
    }

    public function profile(Request $request): View
    {
        $customer = $this->customer($request);
        $pref = \App\Models\NotificationPreference::query()->where('email', strtolower((string) $customer->email))->first();

        return view('desk.profile', [
            'customer' => $customer,
            'pref' => $pref,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        $customer->name = $data['name'];
        $customer->phone = $data['phone'] ?? null;
        $customer->save();

        return back()->with('status', 'Profile saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $customer->password)) {
            return back()->withErrors(['current_password' => 'The current password does not match.']);
        }

        $customer->password = Hash::make($data['password']);
        $customer->last_passwd_gen = now();
        $customer->save();

        return back()->with('status', 'Password updated. The current website can still check this password.');
    }

    private function unreadMessages(Customer $customer): int
    {
        $owned = Order::query()->where('customer_id', $customer->customer_id)->select('order_id');

        return OrderMessage::query()
            ->whereIn('order_id', $owned)
            ->where('to_department', (string) Departments::CUSTOMER)
            ->where('is_viewed', 0)
            ->where('is_deleted', 0)
            ->count();
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return $customer;
    }

    private function owns(Request $request, Order $order): void
    {
        abort_unless((int) $order->customer_id === (int) $this->customer($request)->customer_id, 403);
    }
}
