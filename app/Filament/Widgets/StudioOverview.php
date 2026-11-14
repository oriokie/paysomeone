<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProjectRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudioOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Customers', $this->count(Customer::class)),
            Stat::make('Orders', $this->count(Order::class)),
            Stat::make('Inquiries', $this->count(Order::class, fn ($query) => $query->where('status_id', Order::FREE_INQUIRY))),
            Stat::make('New briefs', $this->count(ProjectRequest::class)),
            Stat::make('Messages', $this->count(ContactMessage::class)),
        ];
    }

    private function count(string $model, ?callable $scope = null): string
    {
        try {
            $query = $model::query();
            if ($scope) {
                $scope($query);
            }

            return (string) $query->count();
        } catch (\Throwable) {
            return '—';
        }
    }
}
