<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Referral;
use App\Models\WalletTransaction;
use App\Support\Departments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeskController extends Controller
{
    public function home(Request $request): View
    {
        $customer = $this->customer($request);
        $orders = Order::query()->where('customer_id', $customer->customer_id)->orderByDesc('date_add');

        return view('desk.home', [
            'customer' => $customer,
            'orders' => (clone $orders)->limit(5)->get(),
            'open' => (clone $orders)->whereNotIn('status_id', [Order::FINISHED, Order::CANCELLED])->count(),
            'alerts' => Notification::query()
                ->where('to_department', (string) Departments::CUSTOMER)
                ->where('to_user_id', $customer->customer_id)
                ->where('is_seen', 0)
                ->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        $customer = $this->customer($request);
        $status = (string) $request->query('status', 'all');
        $groups = $this->groups();

        $orders = Order::query()
            ->where('customer_id', $customer->customer_id)
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->when(isset($groups[$status]), fn ($query) => $query->whereIn('status_id', $groups[$status]))
            ->orderByDesc('date_add')
            ->paginate(12)
            ->withQueryString();

        return view('desk.orders', [
            'orders' => $orders,
            'status' => $status,
            'filters' => [
                'all' => 'All',
                'waiting' => 'Waiting for payment',
                'inquiry' => 'Enquiries',
                'progress' => 'In progress',
                'delivered' => 'Delivered',
                'revision' => 'Revision',
                'finished' => 'Finished',
                'cancelled' => 'Cancelled',
                'dispute' => 'Dispute',
            ],
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->owns($request, $order);
        $order->load(['files', 'messages', 'revisions', 'ratings', 'tips', 'payments']);

        return view('desk.order', [
            'order' => $order,
            'customer' => $this->customer($request),
            'readOnly' => (bool) config('legacy.read_only'),
            'safeMode' => (bool) config('legacy.safe_mode'),
        ]);
    }

    public function wallet(Request $request): View
    {
        $customer = $this->customer($request);

        return view('desk.wallet', [
            'customer' => $customer,
            'transactions' => WalletTransaction::query()->where('customer_id', $customer->customer_id)->orderByDesc('date_add')->limit(30)->get(),
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
        return view('desk.profile', ['customer' => $this->customer($request)]);
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

    /**
     * @return array<string, list<int>>
     */
    private function groups(): array
    {
        return [
            'waiting' => [Order::WAITING_FOR_PAYMENT, Order::CONFIRMATION_PENDING],
            'inquiry' => [Order::FREE_INQUIRY, Order::REPLY_PENDING, Order::INQUIRY_PUBLISHED, Order::INQUIRY_WRITER_ACCEPTED],
            'progress' => [Order::NEW_PAID, Order::ORDER_PUBLISHED, Order::WRITER_ACCEPTED, Order::WRITER_ASSIGNED, Order::DONE],
            'delivered' => [Order::DELIVERED],
            'revision' => [Order::REVISION],
            'finished' => [Order::FINISHED],
            'cancelled' => [Order::CANCELLED],
            'dispute' => [Order::DISPUTE],
        ];
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
