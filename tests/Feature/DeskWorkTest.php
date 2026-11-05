<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderRating;
use App\Models\PaymentIntent;
use App\Support\Departments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeskWorkTest extends TestCase
{
    #[Test]
    public function a_customer_can_upload_and_download_only_their_own_file(): void
    {
        config(['legacy.read_only' => false]);
        $owner = $this->customer('files-owner@example.test');
        $other = $this->customer('files-other@example.test');
        $order = $this->order($owner, Order::WAITING_FOR_PAYMENT);
        $admin = Employee::query()->create([
            'email' => 'files-admin@example.test',
            'employee_group' => Employee::GROUP_ADMIN,
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5('files-admin'),
            'is_started' => 1,
            'is_banned' => 0,
            'reg_date' => now(),
        ]);

        $this->actingAs($owner, 'customer')
            ->post(route('desk.files.store', $order), [
                'upload' => UploadedFile::fake()->create('brief.txt', 2, 'text/plain'),
                'description' => 'The brief',
            ])->assertRedirect()->assertSessionHas('status');

        $file = OrderFile::query()->where('order_id', $order->order_id)->first();
        $this->assertNotNull($file);
        $this->assertSame($owner->customer_id, (int) $file->uploader_id);
        $this->actingAs($owner, 'customer')
            ->get(route('desk.files.download', [$order, $file]))
            ->assertOk();
        $this->actingAs($other, 'customer')
            ->get(route('desk.files.download', [$order, $file]))
            ->assertForbidden();
        $this->assertGreaterThan(0, Notification::query()->where('to_user_id', $admin->employee_id)->count());
    }

    #[Test]
    public function messages_revisions_ratings_and_approval_follow_the_order_status(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('work@example.test');
        $order = $this->order($customer, Order::WAITING_FOR_PAYMENT);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.messages.store', $order), ['to' => 'writer', 'body' => 'Please start with the result.'])
            ->assertSessionHasErrors('body');

        $this->actingAs($customer, 'customer')
            ->post(route('desk.messages.store', $order), ['to' => 'support', 'body' => 'Please confirm the deadline.'])
            ->assertSessionHas('status');
        $this->assertSame(Departments::SUPPORT, (int) OrderMessage::query()->where('order_id', $order->order_id)->value('to_department'));

        $this->actingAs($customer, 'customer')
            ->post(route('desk.revisions.store', $order), ['instructions' => 'Tighten the opening paragraph.', 'hours' => 24])
            ->assertSessionHasErrors('instructions');

        $order->status_id = Order::DELIVERED;
        $order->save();
        $this->actingAs($customer, 'customer')
            ->post(route('desk.revisions.store', $order), ['instructions' => 'Tighten the opening paragraph so the result is first.', 'hours' => 24])
            ->assertSessionHas('status');
        $this->assertSame(Order::REVISION, (int) $order->fresh()->status_id);

        $order = $order->fresh();
        $order->status_id = Order::DELIVERED;
        $order->save();
        $this->actingAs($customer, 'customer')
            ->post(route('desk.orders.accept', $order))
            ->assertSessionHas('status');
        $this->assertSame(Order::FINISHED, (int) $order->fresh()->status_id);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.rating.store', $order), ['service_rating' => 5, 'paper_rating' => 4, 'comment' => 'Clear and on time.'])
            ->assertSessionHas('status');
        $this->assertSame(5, (int) OrderRating::query()->where('order_id', $order->order_id)->value('service_rating'));
    }

    #[Test]
    public function profile_password_notifications_and_payment_filters_stay_on_the_signed_in_account(): void
    {
        config(['legacy.read_only' => false]);
        $customer = $this->customer('profile@example.test');
        $this->actingAs($customer, 'customer')
            ->get(route('desk.profile'))->assertOk()->assertSee('profile@example.test');
        $this->actingAs($customer, 'customer')
            ->post(route('desk.profile.update'), ['name' => 'New Name', 'phone' => '0711000000'])
            ->assertSessionHas('status');
        $this->assertSame('New Name', $customer->fresh()->name);

        $this->actingAs($customer, 'customer')
            ->post(route('desk.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])->assertSessionHasErrors('current_password');
        $this->actingAs($customer, 'customer')
            ->post(route('desk.password.update'), [
                'current_password' => 'long-enough-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])->assertSessionHas('status');
        $this->assertTrue(Hash::check('replacement-password', $customer->fresh()->password));

        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'provider' => 'mpesa',
            'purpose' => 'order',
            'reference' => 'filter-paid',
            'amount' => 11,
            'status' => 'paid',
        ]);
        PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'provider' => 'paypal',
            'purpose' => 'order',
            'reference' => 'filter-pending',
            'amount' => 12,
            'status' => 'pending',
        ]);
        $this->actingAs($customer, 'customer')
            ->get('/account/payments?status=paid&provider=mpesa&q=filter-paid&per_page=10')
            ->assertOk()
            ->assertSee('filter-paid')
            ->assertDontSee('filter-pending');
        $this->actingAs($customer, 'customer')->get(route('desk.notifications'))->assertOk();
        $this->actingAs($customer, 'customer')->get(route('desk.referrals'))->assertOk();
        $this->actingAs($customer, 'customer')->get(route('desk.wallet'))->assertOk();
        $this->actingAs($customer, 'customer')->get(route('account'))->assertOk();
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'google_analytics_id' => $email,
            'email' => $email,
            'name' => 'Client',
            'phone' => '0700000001',
            'country_id' => 0,
            'site_id' => 1,
            'password' => Hash::make('long-enough-password'),
            'secure_key' => md5($email),
            'wallet_balance' => 0,
            'reg_date' => now(),
        ]);
    }

    private function order(Customer $customer, int $status): Order
    {
        $order = new Order;
        $order->site_id = 1;
        $order->form_type_id = Order::SERVICE_ARTICLE;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->status_id = $status;
        $order->parent_id = 0;
        $order->service_type = Order::SERVICE_ARTICLE;
        $order->tariff_id = 0;
        $order->title = 'Desk work order';
        $order->academic_level_id = 0;
        $order->paper_type_id = 0;
        $order->topic_category_id = 0;
        $order->paper_format_id = 0;
        $order->spacing = 'double';
        $order->total = 25;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return $order;
    }
}
