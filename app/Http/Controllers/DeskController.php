<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
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

        return view('desk.home', [
            'customer' => $customer,
            'orders' => (clone $orders)->forCustomerTab('recent')->orderByDesc('order_id')->limit(8)->get(),
            'unpaid' => (clone $orders)->forCustomerTab('unpaid')->count(),
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

        return view('desk.orders', [
            'orders' => $orders,
            'status' => $status,
            'filters' => OrderStatuses::TABS,
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
