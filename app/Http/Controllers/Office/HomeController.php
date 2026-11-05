<?php

namespace App\Http\Controllers\Office;

use App\Models\AuditLog;
use App\Models\CreditFine;
use App\Models\News;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Services\Office\Access;
use App\Services\Office\Board;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends OfficeController
{
    public function __invoke(Request $request): View
    {
        $staff = $this->staff($request);
        $orders = Board::query($staff);
        $mine = Order::query()->where('writer_id', $staff->employee_id);

        return view('office.home', [
            'staff' => $staff,
            'kpis' => [
                'Open orders' => (clone $orders)->whereNotIn('status_id', [Order::FINISHED, Order::CANCELLED])->count(),
                'In review' => (clone $orders)->where('status_id', Order::DONE)->count(),
                'Awaiting payment' => Access::allows($staff, 'orders.view_all')
                    ? Order::query()->where('status_id', Order::WAITING_FOR_PAYMENT)->count()
                    : (clone $mine)->where('status_id', Order::WAITING_FOR_PAYMENT)->count(),
                'My assigned' => (clone $mine)->whereNotIn('status_id', [Order::FINISHED, Order::CANCELLED])->count(),
            ],
            'recent' => (clone $orders)->orderByDesc('order_id')->limit(6)->get(),
            'news' => News::query()->orderByDesc('date_add')->limit(3)->get(),
            'fines' => Access::allows($staff, 'fines.manage') || Access::allows($staff, 'payouts.request')
                ? CreditFine::query()->when(! Access::allows($staff, 'fines.manage'), fn ($q) => $q->where('employee_id', $staff->employee_id))->orderByDesc('date_add')->limit(5)->get()
                : collect(),
            'payouts' => Access::allows($staff, 'payouts.approve')
                ? PaymentRequest::query()->where('status', 0)->count()
                : PaymentRequest::query()->where('user_id', $staff->employee_id)->count(),
            'alerts' => Notification::query()->where('to_user_id', $staff->employee_id)->where('is_seen', 0)->count(),
            'activity' => Access::allows($staff, 'audit.view') ? AuditLog::query()->orderByDesc('id')->limit(6)->get() : collect(),
        ]);
    }
}
