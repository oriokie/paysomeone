<?php

namespace Database\Seeders;

use App\Auth\LegacyHasher;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Discipline;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderRating;
use App\Models\PaperType;
use App\Models\Post;
use App\Models\WalletTransaction;
use App\Support\Departments;
use App\Models\Site;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Sample rows for a local copy of the database only.
 * Refuses the live database name even if someone passes the allow flag.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('legacy.allow_seed')) {
            throw new RuntimeException('Set LEGACY_ALLOW_SEED=true to load demo rows. Never do this on the live database.');
        }

        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($database === (string) config('legacy.live_database')) {
            throw new RuntimeException('Refusing to seed the live database '.$database.'.');
        }

        config(['legacy.read_only' => false]);

        $site = Site::query()->updateOrCreate(
            ['site_id' => 1],
            ['name' => 'PaySomeoneToWrite', 'email' => 'studio@paysomeonetowrite.com', 'domain' => 'new.paysomeonetowrite.com', 'date_add' => now(), 'date_upd' => now()],
        );

        Currency::query()->updateOrCreate(
            ['iso_code' => 'USD'],
            ['title' => 'US Dollar', 'symbol' => '$', 'conversion_rate' => 1, 'active' => 1, 'date_add' => now(), 'date_upd' => now()],
        );

        $this->rates((int) $site->site_id);
        $this->people();
        $this->posts();
    }

    private function rates(int $siteId): void
    {
        $articles = [
            ['24 hours', 24, 12, 0.12],
            ['3 days', 72, 48, 0.08],
            ['5 days', 120, 96, 0.05],
        ];
        foreach ($articles as [$name, $customer, $writer, $rate]) {
            Deadline::query()->updateOrCreate(
                ['site_id' => $siteId, 'name' => $name, 'is_article_writing' => 1],
                [
                    'price_per_page' => $rate,
                    'hrs_customer' => $customer,
                    'hrs_writer' => $writer,
                    'min_pages' => 1,
                    'is_academic_writing' => 0,
                    'is_resume' => 0,
                    'date_add' => now(),
                    'date_upd' => now(),
                ],
            );
        }

        $pages = [
            ['24 hours', 24, 12, 45],
            ['3 days', 72, 48, 32],
            ['7 days', 168, 120, 22],
        ];
        foreach ($pages as [$name, $customer, $writer, $rate]) {
            Deadline::query()->updateOrCreate(
                ['site_id' => $siteId, 'name' => $name, 'is_resume' => 1],
                [
                    'price_per_page' => $rate,
                    'hrs_customer' => $customer,
                    'hrs_writer' => $writer,
                    'min_pages' => 1,
                    'is_academic_writing' => 0,
                    'is_article_writing' => 0,
                    'date_add' => now(),
                    'date_upd' => now(),
                ],
            );
        }

        foreach (['Finance', 'Technology', 'Healthcare', 'Marketing'] as $title) {
            Discipline::query()->updateOrCreate(
                ['title' => $title, 'is_article_writing' => 1],
                ['group_id' => 0, 'is_academic_writing' => 0, 'date_add' => now(), 'date_upd' => now()],
            );
        }

        foreach (['Article', 'Blog post'] as $title) {
            PaperType::query()->updateOrCreate(
                ['title' => $title],
                ['is_article_writing' => 1, 'is_academic_writing' => 0, 'date_add' => now(), 'date_upd' => now()],
            );
        }

        Coupon::query()->updateOrCreate(
            ['coupon_code' => 'WELCOME10', 'site_id' => $siteId],
            ['coupon_type' => Coupon::PERCENT, 'coupon_value' => 10, 'active' => 1, 'one_time' => 0, 'date_add' => now(), 'date_upd' => now()],
        );
    }

    private function people(): void
    {
        $hasher = new LegacyHasher(12);
        $password = $hasher->saltedSha1('Preview-Only-2026', 'preview01');

        $customer = Customer::query()->updateOrCreate(
            ['email' => 'demo@paysomeonetowrite.test', 'site_id' => 1],
            [
                'google_analytics_id' => '',
                'name' => 'Amina Okello',
                'password' => $password,
                'country_id' => 0,
                'secure_key' => md5('demo-customer'),
                'policy_accepted' => 1,
                'wallet_balance' => 40,
                'reg_date' => now(),
            ],
        );

        if ((int) $customer->customer_id === 0) {
            $customer->customer_id = 452441;
            $customer->save();
        }

        Employee::query()->updateOrCreate(
            ['email' => 'office@paysomeonetowrite.test'],
            [
                'employee_group' => Employee::GROUP_ADMIN,
                'first_name' => 'Edwin',
                'last_name' => 'Preview',
                'password' => $hasher->make('Office-Preview-2026'),
                'secure_key' => md5('demo-office'),
                'is_started' => 1,
                'is_banned' => 0,
                'two_factor_enabled' => 0,
                'reg_date' => now(),
            ],
        );

        $order = Order::query()->firstOrCreate(
            ['tracking_id' => 'DEMOREVIEW1'],
            [
                'site_id' => 1,
                'form_type_id' => Order::SERVICE_ARTICLE,
                'form_type' => 'professional',
                'gmt' => 0,
                'customer_id' => $customer->customer_id,
                'status_id' => Order::FINISHED,
                'parent_id' => 0,
                'service_type' => Order::SERVICE_ARTICLE,
                'tariff_id' => (int) (Deadline::query()->where('is_article_writing', 1)->value('deadline_id') ?: 0),
                'title' => 'Notes for a product launch',
                'academic_level_id' => 0,
                'paper_type_id' => 0,
                'topic_category_id' => 0,
                'paper_format_id' => 0,
                'pages' => 0,
                'words' => 800,
                'spacing' => 'double',
                'paper_details' => 'Demo brief for local reviews.',
                'total' => 40,
                'raw_cost' => 40,
                'price_per_word' => 0.05,
                'order_type' => 'article',
                'is_paid' => 1,
                'total_paid' => 40,
                'date_add' => now()->subDays(12),
                'date_upd' => now()->subDays(10),
            ],
        );

        OrderRating::query()->updateOrCreate(
            ['order_id' => $order->order_id, 'user_type' => 'customer'],
            [
                'user_id' => $customer->customer_id,
                'paper_rating' => 5,
                'service_rating' => 5,
                'paper_on_time' => 1,
                'comment' => 'The launch note sounded like us, and the second draft answered the questions our sales lead actually gets on calls.',
                'date_add' => now()->subDays(9),
                'date_upd' => now()->subDays(9),
            ],
        );

        OrderMessage::query()->updateOrCreate(
            ['order_id' => $order->order_id, 'from_department' => Departments::SUPPORT],
            [
                'sender_id' => 0,
                'parent_id' => 0,
                'to_department' => Departments::CUSTOMER,
                'subject' => 'Order #'.$order->order_id,
                'body' => 'The launch note is ready. Tell us if the opening should be shorter before you approve it.',
                'date_add' => now()->subDays(8),
                'date_upd' => now()->subDays(8),
            ],
        );

        Order::query()->firstOrCreate(
            ['tracking_id' => 'DEMOUNPAID'],
            [
                'site_id' => 1,
                'form_type_id' => Order::SERVICE_ARTICLE,
                'form_type' => 'articles',
                'gmt' => 0,
                'customer_id' => $customer->customer_id,
                'status_id' => Order::WAITING_FOR_PAYMENT,
                'parent_id' => 0,
                'service_type' => Order::SERVICE_ARTICLE,
                'tariff_id' => (int) (Deadline::query()->where('is_article_writing', 1)->value('deadline_id') ?: 0),
                'title' => 'Quarterly client update',
                'academic_level_id' => 0,
                'paper_type_id' => 0,
                'topic_category_id' => 0,
                'paper_format_id' => 0,
                'pages' => 0,
                'words' => 800,
                'spacing' => 'double',
                'paper_details' => 'An update for existing clients. Lead with the result, then the next step.',
                'total' => 96,
                'raw_cost' => 96,
                'price_per_word' => 0.12,
                'order_type' => 'article',
                'is_paid' => 0,
                'date_add' => now()->subDay(),
                'date_upd' => now()->subDay(),
            ],
        );

        WalletTransaction::query()->firstOrCreate(
            ['transaction_id' => 'DEMOWALLET1'],
            [
                'customer_id' => $customer->customer_id,
                'source' => 'wallet',
                'payment_method' => 'mpesa',
                'amount' => 40,
                'date_add' => now()->subDays(20),
            ],
        );

        Notification::query()->firstOrCreate(
            ['node_url' => (string) $order->order_id, 'to_user_id' => $customer->customer_id, 'node_type' => 'new_payment'],
            [
                'to_department' => (string) Departments::CUSTOMER,
                'from_department' => (string) Departments::SUPPORT,
                'from_user_id' => 0,
                'action' => 'order',
                'message' => 'Payment received for order #'.$order->order_id,
                'is_seen' => 0,
                'date_add' => now()->subDays(11),
                'date_upd' => now()->subDays(11),
            ],
        );

        $upload = storage_path('app/legacy-uploads/'.$order->order_id);
        if (! is_dir($upload)) {
            mkdir($upload, 0750, true);
        }
        file_put_contents($upload.'/brief.txt', 'Demo brief stored outside the web root.');
        OrderFile::query()->updateOrCreate(
            ['order_id' => $order->order_id, 'source' => $order->order_id.'/brief.txt'],
            [
                'site_id' => 1,
                'uploader_id' => $customer->customer_id,
                'file_type_id' => 1,
                'file_description' => 'Original brief',
                'name' => 'brief.txt',
                'size' => 42,
                'extension' => 'txt',
                'date_add' => now()->subDays(11),
                'date_upd' => now()->subDays(11),
            ],
        );

        \App\Models\MailTemplate::query()->updateOrCreate(
            ['template' => 'order_payment'],
            [
                'subject' => 'Payment received for order #{order_id}',
                'from_name' => 'PaySomeoneToWrite',
                'message' => "Hello {username},\n\nWe received {paid_amount} by {payment_method} for “{title}”.\n",
                'is_disabled' => 0,
                'date_add' => now(),
                'date_upd' => now(),
            ],
        );

        OrderRating::query()->updateOrCreate(
            ['order_id' => $order->order_id, 'user_type' => 'customer', 'service_rating' => 4],
            [
                'user_id' => $customer->customer_id,
                'paper_rating' => 5,
                'service_rating' => 4,
                'paper_on_time' => 1,
                'comment' => 'The resume edit cut the filler and kept the promotions that matter. I sent it the same afternoon.',
                'date_add' => now()->subDays(4),
                'date_upd' => now()->subDays(4),
            ],
        );
    }

    private function posts(): void
    {
        $notes = [
            [
                'title' => 'What to put in a brief so the first draft is usable',
                'slug' => 'what-to-put-in-a-brief',
                'excerpt' => 'A writer can guess at tone. They cannot guess who has to say yes.',
                'body' => "## Start with the reader\n\nName the person who will read the piece and what you want them to do next. A price, a meeting, a reply.\n\n## Send the lines you already like\n\nPaste a sentence that sounds like the company. It is more useful than a list of adjectives.\n\n## Mark what must not change\n\nProduct names, numbers, and legal lines. Everything else can be rewritten.",
            ],
            [
                'title' => 'Proofreading is not the same job as rewriting',
                'slug' => 'proofreading-is-not-rewriting',
                'excerpt' => 'One fixes the surface. The other rebuilds the argument. They are priced differently for that reason.',
                'body' => "## Proofreading\n\nSpelling, consistency, and the small slips that make a finished document look unfinished.\n\n## Rewriting\n\nThe point is buried, the opening is an apology, or three authors are still visible. That is a new draft, not a tidy-up.\n\nIf you are unsure, send the draft and say what feels wrong. The quote will follow the job, not the label.",
            ],
            [
                'title' => 'A resume editor does not invent a career',
                'slug' => 'a-resume-editor-does-not-invent-a-career',
                'excerpt' => 'The useful edit is specific. The dangerous one adds a job you did not have.',
                'body' => "Send the real dates, the real titles, and one result for each role that a hiring manager can check.\n\nWe will cut the duties list, put the result first, and leave out anything you cannot stand behind in an interview.\n\nThat is the whole service.",
            ],
        ];

        foreach ($notes as $note) {
            Post::query()->updateOrCreate(
                ['slug' => $note['slug']],
                $note + [
                    'author_name' => 'Studio',
                    'meta_description' => $note['excerpt'],
                    'published_at' => now()->subDays(3),
                ],
            );
        }
    }
}
