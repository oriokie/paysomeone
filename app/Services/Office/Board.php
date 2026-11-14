<?php

namespace App\Services\Office;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class Board
{
    public static function query(Employee $staff): Builder
    {
        $query = Order::query();
        if (Access::allows($staff, 'orders.view_all')) {
            return $query;
        }
        if (Access::allows($staff, 'orders.view_review') && ! Access::allows($staff, 'orders.view_own')) {
            return $query->whereIn('status_id', [Order::DONE, Order::REVISION, Order::DELIVERED]);
        }

        return $query->where(function (Builder $inner) use ($staff) {
            $inner->where('writer_id', $staff->employee_id)
                ->orWhere('editor_id', $staff->employee_id);
            if (Access::allows($staff, 'orders.view_review')) {
                $inner->orWhereIn('status_id', [Order::DONE, Order::REVISION, Order::DELIVERED]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function customer(Employee $staff, ?Customer $customer): array
    {
        if (! $customer) {
            return ['name' => 'Customer', 'email' => null, 'phone' => null];
        }
        if (! Access::seesPii($staff)) {
            return ['name' => 'Customer #'.$customer->customer_id, 'email' => null, 'phone' => null];
        }

        return [
            'name' => (string) ($customer->name ?: 'Customer'),
            'email' => (string) $customer->email,
            'phone' => (string) ($customer->phone ?? ''),
        ];
    }

    /**
     * @return array<string, list<int>>
     */
    public static function lanes(): array
    {
        return [
            'Waiting' => [Order::WAITING_FOR_PAYMENT, Order::CONFIRMATION_PENDING],
            'Pool' => [Order::ORDER_PUBLISHED, Order::INQUIRY_PUBLISHED],
            'Assigned' => [Order::WRITER_ASSIGNED, Order::WRITER_ACCEPTED],
            'Review' => [Order::DONE],
            'Delivered' => [Order::DELIVERED],
            'Revision' => [Order::REVISION],
            'Finished' => [Order::FINISHED],
            'Closed' => [Order::CANCELLED, Order::DISPUTE],
        ];
    }
}
