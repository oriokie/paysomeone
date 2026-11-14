<?php

namespace App\Providers;

use App\Auth\LegacyHasher;
use App\Auth\LegacyUserProvider;
use App\Exceptions\LegacyWriteBlocked;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\Site;
use App\Support\Departments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'hashing.driver' => 'legacy',
            'session.table' => env('SESSION_TABLE', 'app_sessions'),
            'cache.stores.database.table' => env('DB_CACHE_TABLE', 'app_cache'),
            'cache.stores.database.lock_table' => env('DB_CACHE_LOCK_TABLE', 'app_cache_locks'),
            'queue.connections.database.table' => env('DB_QUEUE_TABLE', 'app_jobs'),
            'queue.batching.table' => 'app_job_batches',
            'queue.failed.table' => 'app_failed_jobs',
        ]);

        $this->app->booting(function () {
            Hash::extend('legacy', function () {
                return new LegacyHasher((int) config('hashing.bcrypt.rounds', 12));
            });
        });

        Auth::provider('legacy-eloquent', function ($app, array $config) {
            return new LegacyUserProvider($app['hash'], $config['model']);
        });
    }

    public function boot(): void
    {
        DB::beforeExecuting(function (string $query): void {
            if (! config('legacy.read_only')) {
                return;
            }

            if (! preg_match('/^\s*(insert|update|delete|replace|alter|drop|truncate|create)\b/i', $query)) {
                return;
            }

            if (preg_match('/\bprox_[a-z0-9_]+/i', $query)) {
                throw new LegacyWriteBlocked('prox_');
            }
        });

        View::composer('*', function ($view): void {
            $name = (string) config('app.name', 'PaySomeoneToWrite');

            try {
                $site = Site::query()->find(config('legacy.site_id')) ?? Site::query()->first();
                if ($site?->name) {
                    $name = $site->name;
                }
            } catch (\Throwable) {
                // The public pages still render when the database is not loaded yet.
            }

            $view->with('siteName', $name);
            $view->with('readOnly', (bool) config('legacy.read_only'));
            $view->with('safeMode', (bool) config('legacy.safe_mode'));
        });

        View::composer('layouts.desk', function ($view): void {
            $notes = collect();
            $unreadNotes = 0;
            $unreadMessages = 0;
            $customer = auth('customer')->user();

            if ($customer) {
                try {
                    $notes = Notification::query()
                        ->where('to_department', (string) Departments::CUSTOMER)
                        ->where('to_user_id', $customer->customer_id)
                        ->orderByDesc('date_add')
                        ->limit(5)
                        ->get();
                    $unreadNotes = Notification::query()
                        ->where('to_department', (string) Departments::CUSTOMER)
                        ->where('to_user_id', $customer->customer_id)
                        ->where('is_seen', 0)
                        ->count();
                    $owned = Order::query()->where('customer_id', $customer->customer_id)->select('order_id');
                    $unreadMessages = OrderMessage::query()
                        ->whereIn('order_id', $owned)
                        ->where('to_department', (string) Departments::CUSTOMER)
                        ->where('is_viewed', 0)
                        ->where('is_deleted', 0)
                        ->count();
                } catch (\Throwable) {
                    $notes = collect();
                }
            }

            $view->with('deskNotes', $notes);
            $view->with('deskUnread', $unreadNotes);
            $view->with('deskUnreadMessages', $unreadMessages);
        });
    }
}
