<?php

namespace App\Console\Commands;

use App\Models\DeadlineAlert;
use App\Models\Employee;
use App\Models\JobHeartbeat;
use App\Models\Order;
use App\Services\Desk\Notifier;
use Illuminate\Console\Command;

class DeadlineAlerts extends Command
{
    protected $signature = 'alerts:deadlines';

    protected $description = 'Email writers, editors, and customers when a deadline is inside 24 hours or already past';

    public function handle(Notifier $notifier): int
    {
        $sent = 0;
        $orders = Order::query()
            ->whereIn('status_id', [Order::WRITER_ASSIGNED, Order::WRITER_ACCEPTED, Order::REVISION, Order::DONE])
            ->where(function ($query) {
                $query->whereBetween('writer_deadline', [now()->subDays(3), now()->addDay()])
                    ->orWhereBetween('customer_deadline', [now()->subDays(3), now()->addDay()]);
            })
            ->get();

        foreach ($orders as $order) {
            $exists = DeadlineAlert::query()->where('order_id', $order->order_id)->whereDate('alert_on', today())->exists();
            if ($exists) {
                continue;
            }
            DeadlineAlert::query()->create(['order_id' => $order->order_id, 'alert_on' => today()]);
            $sent++;
            $lines = ['order_id' => $order->order_id, 'title' => $order->title];
            foreach (array_filter([$order->writer_id, $order->editor_id]) as $employeeId) {
                $person = Employee::query()->find($employeeId);
                if ($person && $person->email) {
                    $notifier->email('deadline_staff', (string) $person->email, $lines, 'Deadline approaching on order #'.$order->order_id, 'orders', false, route('office.orders.show', $order));
                }
            }
            $customer = $order->customer;
            if ($customer && $customer->email) {
                $notifier->email('deadline_customer', (string) $customer->email, $lines, 'Your order #'.$order->order_id.' is nearing its deadline', 'orders', false, route('desk.order', $order));
            }
        }

        JobHeartbeat::query()->updateOrCreate(['name' => 'alerts:deadlines'], [
            'status' => 'ok',
            'detail' => $sent.' alerts',
            'ran_at' => now(),
        ]);
        $this->info($sent.' deadline alerts sent.');

        return self::SUCCESS;
    }
}
