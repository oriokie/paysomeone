<?php

namespace Database\Seeders;

use App\Auth\LegacyHasher;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderBid;
use App\Models\Site;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Local journey data only. Refuses the live database name.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($database === (string) config('legacy.live_database')) {
            throw new RuntimeException('Refusing to seed the live database.');
        }
        config(['legacy.read_only' => false]);
        $hasher = new LegacyHasher(4);
        Site::query()->updateOrCreate(['site_id' => 1], ['name' => 'PaySomeoneToWrite', 'email' => 'studio@paysomeonetowrite.com', 'domain' => 'local.test', 'date_add' => now(), 'date_upd' => now()]);

        $this->customer('e2e-bcrypt@example.test', $hasher->make('E2e-Bcrypt-2026'));
        $this->customer('e2e-md5@example.test', md5('E2e-Md5-2026'));
        $this->customer('e2e-sha@example.test', $hasher->saltedSha1('E2e-Sha-2026', 'saltsha01'));

        Employee::query()->updateOrCreate(['email' => 'e2e-enrol@example.test'], [
            'employee_group' => Employee::GROUP_ADMIN,
            'first_name' => 'Enid',
            'last_name' => 'E2E',
            'password' => $hasher->make('E2e-Staff-2026'),
            'secure_key' => md5('e2e-enrol@example.test'),
            'is_started' => 1,
            'is_banned' => 0,
            'two_factor_enabled' => 0,
            'two_factor_type' => null,
            'two_factor_gsecret' => null,
            'writer_category' => 0,
            'reg_date' => now(),
        ]);

        foreach ([
            ['e2e-admin@example.test', Employee::GROUP_ADMIN, 'Ada', 0],
            ['e2e-sub@example.test', Employee::GROUP_SUB_ADMIN, 'Sam', 0],
            ['e2e-editor@example.test', Employee::GROUP_EDITOR, 'Eve', 0],
            ['e2e-writer@example.test', Employee::GROUP_WRITER, 'Wes', 2],
        ] as [$email, $group, $name, $category]) {
            Employee::query()->updateOrCreate(['email' => $email], [
                'employee_group' => $group,
                'first_name' => $name,
                'last_name' => 'E2E',
                'password' => $hasher->make('E2e-Staff-2026'),
                'secure_key' => md5($email),
                'is_started' => 1,
                'is_banned' => 0,
                'two_factor_enabled' => 1,
                'two_factor_type' => 'google',
                'two_factor_gsecret' => 'JBSWY3DPEHPK3PXP',
                'writer_category' => $category,
                'reg_date' => now(),
            ]);
        }

        Coupon::query()->updateOrCreate(['coupon_code' => 'E2E10', 'site_id' => 1], [
            'coupon_type' => Coupon::PERCENT, 'coupon_value' => 10, 'active' => 1, 'one_time' => 0, 'date_add' => now(), 'date_upd' => now(),
        ]);

        $customer = Customer::query()->where('email', 'e2e-bcrypt@example.test')->first();
        $writer = Employee::query()->where('email', 'e2e-writer@example.test')->first();
        $pool = Order::query()->where('tracking_id', 'E2EPOOL')->first();
        if ($pool) {
            $pool->status_id = Order::ORDER_PUBLISHED;
            $pool->writer_id = 0;
            $pool->date_upd = now();
            $pool->save();
            OrderBid::query()->where('order_id', $pool->order_id)->update(['is_deleted' => 1]);
        }
        if ($customer && ! $pool) {
            $order = new Order;
            $order->site_id = 1;
            $order->tracking_id = 'E2EPOOL';
            $order->form_type_id = Order::SERVICE_ARTICLE;
            $order->form_type = 'articles';
            $order->gmt = 0;
            $order->customer_id = $customer->customer_id;
            $order->status_id = Order::ORDER_PUBLISHED;
            $order->parent_id = 0;
            $order->service_type = Order::SERVICE_ARTICLE;
            $order->tariff_id = 0;
            $order->title = 'E2E pool brief';
            $order->academic_level_id = 0;
            $order->paper_type_id = 0;
            $order->topic_category_id = 52;
            $order->topic_category_option = 'Professional';
            $order->paper_format_id = 4;
            $order->paper_format_option = 'Not applicable';
            $order->spacing = 'double';
            $order->paper_details = 'A professional brief already in the writer pool.';
            $order->total = 40;
            $order->is_paid = 1;
            $order->total_paid = 40;
            $order->order_type = 'article';
            $order->writer_category_id = 2;
            $order->date_add = now();
            $order->date_upd = now();
            $order->save();
        }
        if ($customer && $writer && ! Order::query()->where('tracking_id', 'E2EREVIEW')->exists()) {
            $order = new Order;
            $order->site_id = 1;
            $order->tracking_id = 'E2EREVIEW';
            $order->form_type_id = Order::SERVICE_ARTICLE;
            $order->gmt = 0;
            $order->customer_id = $customer->customer_id;
            $order->writer_id = $writer->employee_id;
            $order->status_id = Order::DONE;
            $order->parent_id = 0;
            $order->service_type = Order::SERVICE_ARTICLE;
            $order->tariff_id = 0;
            $order->title = 'E2E review brief';
            $order->academic_level_id = 0;
            $order->paper_type_id = 0;
            $order->topic_category_id = 52;
            $order->paper_format_id = 4;
            $order->spacing = 'double';
            $order->paper_details = 'Waiting for an editor.';
            $order->total = 40;
            $order->is_paid = 1;
            $order->order_type = 'article';
            $order->date_add = now();
            $order->date_upd = now();
            $order->save();
        }
    }

    private function customer(string $email, string $password): void
    {
        Customer::query()->updateOrCreate(['email' => $email], [
            'google_analytics_id' => $email,
            'name' => 'E2E Client',
            'password' => $password,
            'country_id' => 0,
            'site_id' => 1,
            'secure_key' => md5($email),
            'reg_date' => now(),
        ]);
    }
}
