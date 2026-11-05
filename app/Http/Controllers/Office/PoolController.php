<?php

namespace App\Http\Controllers\Office;

use App\Models\Order;
use App\Models\OrderBid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoolController extends OfficeController
{
    public function index(Request $request): View
    {
        $staff = $this->permit($request, 'bids.place');
        $category = (int) $staff->writer_category;
        $orders = Order::query()
            ->where('status_id', Order::ORDER_PUBLISHED)
            ->when($category > 2, fn ($query) => $query->whereIn('writer_category_id', [0, 2, $category]))
            ->orderByDesc('order_id')
            ->paginate(20);

        return view('office.pool', [
            'staff' => $staff,
            'orders' => $orders,
            'mine' => OrderBid::query()->where('writer_id', $staff->employee_id)->where('is_deleted', 0)->pluck('order_id')->all(),
        ]);
    }

    public function bid(Request $request, Order $order): RedirectResponse
    {
        $staff = $this->permit($request, 'bids.place');
        abort_unless((int) $order->status_id === Order::ORDER_PUBLISHED, 422);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);
        $existing = OrderBid::query()
            ->where('order_id', $order->order_id)
            ->where('writer_id', $staff->employee_id)
            ->where('is_deleted', 0)
            ->first();
        $bid = $existing ?? new OrderBid;
        $bid->order_id = $order->order_id;
        $bid->writer_id = $staff->employee_id;
        $bid->amount = $data['amount'];
        $bid->bid_time = $data['hours'];
        $bid->is_deleted = 0;
        $bid->date_add = $bid->date_add ?: now();
        $bid->date_upd = now();
        $bid->save();
        $this->audit($staff, 'bid.place', 'order', (string) $order->order_id, ['amount' => $data['amount']]);

        return back()->with('status', 'Bid saved.');
    }
}
