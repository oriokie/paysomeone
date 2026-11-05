<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\ContentFeel;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Discipline;
use App\Models\EnglishType;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\PaperFormat;
use App\Models\PaperType;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\TargetAudience;
use App\Models\WritingStyle;
use App\Models\WritingTone;
use App\Services\Orders\OrderFileStore;
use App\Support\OrderDefaults;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeskTest extends TestCase
{
    #[Test]
    public function a_signed_in_customer_places_an_order_in_the_existing_table_when_writes_are_allowed(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('buyer@example.test');
        $deadline = $this->deadline();

        $this->actingAs($customer, 'customer')->post('/order', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 500,
            'intent' => 'pay',
            'title' => 'Board update',
            'brief' => 'A board update for the leadership team, with the decision we need from them.',
            'terms' => '1',
        ])->assertRedirect();

        $order = Order::query()->where('title', 'Board update')->first();
        $this->assertNotNull($order);
        $this->assertSame((int) $customer->customer_id, (int) $order->customer_id);
        $this->assertSame(Order::WAITING_FOR_PAYMENT, (int) $order->status_id);
        $this->assertSame(25.0, (float) $order->total);
        $this->assertSame(0, (int) $order->is_paid);
    }

    #[Test]
    public function read_only_mode_blocks_placing_an_order_and_says_so(): void
    {
        $deadline = $this->deadline();
        config(['legacy.read_only' => true]);

        $this->post('/order', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 500,
            'title' => 'Should not be filed',
            'brief' => 'This brief is long enough to pass validation and must not be filed.',
            'name' => 'Preview',
            'email' => 'preview@example.test',
            'terms' => '1',
        ])->assertRedirect('/order')->assertSessionHasErrors('legacy');

        $this->assertDatabaseMissing('prox_order', ['title' => 'Should not be filed']);
    }

    #[Test]
    public function a_customer_can_download_only_files_on_their_own_orders(): void
    {
        config(['legacy.read_only' => false, 'legacy.uploads_path' => storage_path('framework/testing/order-files')]);
        $owner = $this->customer('owner@example.test');
        $other = $this->customer('other@example.test');
        $order = $this->order($owner);
        $foreign = $this->order($other);

        $file = app(OrderFileStore::class)->store($order, (int) $owner->customer_id, UploadedFile::fake()->create('brief.txt', 2, 'text/plain'), 'Brief');

        $this->assertStringStartsWith(storage_path('framework/testing/order-files'), app(OrderFileStore::class)->absolutePath($file->source));
        $this->assertStringNotContainsString(public_path(), app(OrderFileStore::class)->absolutePath($file->source));

        $this->actingAs($other, 'customer')
            ->get(route('desk.files.download', [$order, $file]))
            ->assertForbidden();

        $this->actingAs($other, 'customer')
            ->get(route('desk.order', $order))
            ->assertForbidden();

        $this->actingAs($owner, 'customer')
            ->get(route('desk.files.download', [$order, $file]))
            ->assertOk();

        $this->actingAs($owner, 'customer')
            ->get(route('desk.files.download', [$foreign, $file]))
            ->assertForbidden();
    }

    #[Test]
    public function a_stripe_callback_is_applied_once(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.stripe.webhook_secret' => 'whsec_test',
        ]);
        $customer = $this->customer('payer@example.test');
        $order = $this->order($customer, 40);

        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'stripe',
            'purpose' => 'order',
            'reference' => 'ref_stripe_1',
            'amount' => 40,
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'client_reference_id' => 'ref_stripe_1',
                'payment_intent' => 'pi_once',
                'amount_total' => 4000,
            ]],
        ], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $headers = ['Stripe-Signature' => 't='.$timestamp.',v1='.$signature];
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars(array_merge($headers, ['CONTENT_TYPE' => 'application/json'])), $payload)->assertOk();
        $this->call('POST', '/payments/stripe/webhook', [], [], [], $this->transformHeadersToServerVars(array_merge($headers, ['CONTENT_TYPE' => 'application/json'])), $payload)->assertOk();

        $this->assertSame(1, Payment::query()->where('transaction_id', 'pi_once')->count());
        $order->refresh();
        $this->assertTrue((bool) $order->is_paid);
        $this->assertSame(40.0, (float) $order->total_paid);
    }

    #[Test]
    public function an_mpesa_callback_is_rejected_without_its_secret_and_applied_once_with_it(): void
    {
        config([
            'legacy.read_only' => false,
            'legacy.safe_mode' => false,
            'services.mpesa.callback_secret' => 'callback-secret',
        ]);
        $customer = $this->customer('mpesa@example.test');
        $order = $this->order($customer, 15);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order->order_id,
            'provider' => 'mpesa',
            'purpose' => 'order',
            'reference' => 'ws_CO_123',
            'amount' => 15,
            'status' => 'pending',
        ]);

        $body = ['Body' => ['stkCallback' => [
            'ResultCode' => 0,
            'CheckoutRequestID' => 'ws_CO_123',
            'CallbackMetadata' => ['Item' => [['Name' => 'MpesaReceiptNumber', 'Value' => 'RCPT1']]],
        ]]];

        $this->postJson('/payments/mpesa/callback', $body)->assertUnauthorized();

        $this->postJson('/payments/mpesa/callback?token=callback-secret', $body)->assertOk();
        $this->postJson('/payments/mpesa/callback?token=callback-secret', $body)->assertOk();

        $this->assertSame(1, Payment::query()->where('transaction_id', 'RCPT1')->count());
        $this->assertSame(15.0, (float) $order->fresh()->total_paid);
    }

    #[Test]
    public function a_new_order_writes_the_current_columns_without_academic_choices(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('columns@example.test');
        $deadline = $this->deadline();
        $type = PaperType::query()->create([
            'title' => 'Board memo',
            'is_article_writing' => 1,
            'is_academic_writing' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $essay = PaperType::query()->create([
            'title' => 'Admission essay',
            'is_article_writing' => 0,
            'is_academic_writing' => 1,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $english = EnglishType::query()->create(['title' => 'American', 'date_add' => now(), 'date_upd' => now()]);
        $audience = TargetAudience::query()->create(['title' => 'Executives', 'date_add' => now(), 'date_upd' => now()]);
        $tone = WritingTone::query()->create(['title' => 'Professional', 'description' => 'Plain.', 'date_add' => now(), 'date_upd' => now()]);
        $style = WritingStyle::query()->create(['title' => 'Informative', 'description' => 'Explains.', 'date_add' => now(), 'date_upd' => now()]);
        $feel = ContentFeel::query()->create(['title' => 'Straightforward', 'description' => 'No decoration.', 'date_add' => now(), 'date_upd' => now()]);

        $this->actingAs($customer, 'customer')
            ->get('/order')
            ->assertOk()
            ->assertSee('Type of content')
            ->assertSee('Target audience')
            ->assertSee('Writing tone')
            ->assertSee('Board memo')
            ->assertDontSee('Admission essay')
            ->assertDontSee('name="academiclevel"', false)
            ->assertDontSee('name="paperformat"', false);

        $this->actingAs($customer, 'customer')->post('/order', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 500,
            'intent' => 'pay',
            'title' => 'Board update columns',
            'brief' => 'A board update for the leadership team, with the decision we need from them.',
            'terms' => '1',
            'paper_type_id' => $essay->paper_type_id,
            'keywords' => 'board, decision',
        ])->assertSessionHasErrors('paper_type_id');

        $this->actingAs($customer, 'customer')->post('/order', [
            'service' => 'articles',
            'deadline_id' => $deadline->deadline_id,
            'words' => 500,
            'writer_category_id' => 3,
            'intent' => 'pay',
            'title' => 'Board update columns',
            'brief' => 'A board update for the leadership team, with the decision we need from them.',
            'terms' => '1',
            'paper_type_id' => $type->paper_type_id,
            'keywords' => 'board, decision',
            'english_type_id' => $english->english_type_id,
            'target_audience_id' => $audience->target_audience_id,
            'writing_tone_id' => $tone->writing_tone_id,
            'writing_style_id' => $style->writing_style_id,
            'content_feel_id' => $feel->content_feel_id,
        ])->assertRedirect();

        $order = Order::query()->where('title', 'Board update columns')->first();
        $this->assertNotNull($order);
        $this->assertSame(Order::SERVICE_ARTICLE, (int) $order->service_type);
        $this->assertSame('article', $order->order_type);
        $this->assertSame((int) $type->paper_type_id, (int) $order->paper_type_id);
        $this->assertSame('Board memo', $order->paper_type_option);
        $this->assertSame(500, (int) $order->words);
        $this->assertSame('board, decision', $order->keywords);
        $this->assertSame((int) $english->english_type_id, (int) $order->english_type_id);
        $this->assertSame((int) $audience->target_audience_id, (int) $order->target_audience_id);
        $this->assertSame((int) $tone->writing_tone_id, (int) $order->writing_tone_id);
        $this->assertSame((int) $style->writing_style_id, (int) $order->writing_style_id);
        $this->assertSame((int) $feel->content_feel_id, (int) $order->content_feel_id);
        $this->assertSame(3, (int) $order->writer_category_id);
        $this->assertSame(25, (int) $order->writer_percent);
        $this->assertSame(OrderDefaults::ACADEMIC_LEVEL_ID, (int) $order->academic_level_id);
        $this->assertSame(OrderDefaults::PAPER_FORMAT_ID, (int) $order->paper_format_id);
        $this->assertSame(OrderDefaults::PAPER_FORMAT_OPTION, $order->paper_format_option);
        $this->assertSame(OrderDefaults::TOPIC_CATEGORY_ID, (int) $order->topic_category_id);
        $this->assertSame(OrderDefaults::TOPIC_CATEGORY_OPTION, $order->topic_category_option);
        $this->assertSame('double', $order->spacing);
        $this->assertSame(Order::WAITING_FOR_PAYMENT, (int) $order->status_id);
    }

    #[Test]
    public function a_legacy_academic_order_renders_its_recorded_fields_read_only(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('legacy@example.test');
        $level = AcademicLevel::query()->create([
            'title' => 'Undergraduate (years 3-4)',
            'short_title' => 'Undergrad',
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $format = PaperFormat::query()->create([
            'title' => 'APA',
            'date_add' => now(),
            'date_upd' => now(),
        ]);
        $subject = Discipline::query()->create([
            'title' => 'Finance legacy',
            'group_id' => 0,
            'is_academic_writing' => 1,
            'is_article_writing' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);

        $order = $this->order($customer);
        $order->service_type = Order::SERVICE_PAGES;
        $order->form_type = 'academic';
        $order->order_type = 'academic';
        $order->academic_level_id = $level->academic_level_id;
        $order->paper_format_id = $format->paper_format_id;
        $order->paper_format_option = 'APA';
        $order->topic_category_id = $subject->discipline_id;
        $order->topic_category_option = 'Finance legacy';
        $order->paper_type_option = 'Research paper';
        $order->pages = 6;
        $order->title = 'Existing finance paper';
        $order->status_id = Order::DELIVERED;
        $order->save();

        $this->actingAs($customer, 'customer')
            ->get(route('desk.order', $order))
            ->assertOk()
            ->assertSee('Existing finance paper')
            ->assertSee('Academic Writing')
            ->assertSee('Recorded on the previous form')
            ->assertSee('Undergraduate (years 3-4)')
            ->assertSee('APA')
            ->assertSee('Finance legacy')
            ->assertSee('Research paper')
            ->assertSee('Delivered')
            ->assertDontSee('name="academic_level"', false)
            ->assertSee('Bids')
            ->assertSee('Looking for writer');
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Desk Client',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'wallet_balance' => 100,
            'reg_date' => now(),
        ]);
    }

    private function deadline(): Deadline
    {
        return Deadline::query()->create([
            'site_id' => 1,
            'name' => '5 days',
            'price_per_page' => 0.05,
            'hrs_customer' => 120,
            'hrs_writer' => 96,
            'min_pages' => 1,
            'is_article_writing' => 1,
            'is_academic_writing' => 0,
            'date_add' => now(),
            'date_upd' => now(),
        ]);
    }

    private function order(Customer $customer, float $total = 20): Order
    {
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_ARTICLE;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->status_id = Order::WAITING_FOR_PAYMENT;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_ARTICLE;
        $order->tariff_id = 0;
        $order->title = 'Order for '.$customer->email;
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->total = $total;
        $order->raw_cost = $total;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
