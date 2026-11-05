<?php

namespace App\Services\Orders;

use App\Events\DeskEvent;
use App\Models\Configuration;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Order;
use App\Services\Desk\Notifier;
use App\Services\Pricing\Quote;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderPlacer
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function place(array $input, Quote $quote, Customer $customer, Deadline $deadline): Order
    {
        $service = (string) $input['service'];
        $words = Services::usesWords($service);
        $currency = Currency::query()->where('iso_code', 'USD')->first();
        $coupon = $this->coupon($input['coupon_code'] ?? null);
        $intent = ($input['intent'] ?? 'pay') === 'inquiry' ? 'inquiry' : 'pay';
        $total = $quote->amountDue();

        $status = Order::WAITING_FOR_PAYMENT;
        if ($total <= 0) {
            $status = Order::NEW_PAID;
        } elseif ($intent === 'inquiry') {
            $status = Order::FREE_INQUIRY;
        }

        $order = new Order;
        $order->order_id = $this->nextOrderId();
        $order->site_id = (int) ($customer->site_id ?: config('legacy.site_id'));
        $order->tracking_id = 'NW'.strtoupper(Str::random(8));
        $order->form_type_id = $words ? Order::SERVICE_ARTICLE : Order::SERVICE_PAGES;
        $order->form_type = $service;
        $order->currency_id = $currency?->currency_id;
        $order->currency_to_usd_rate = $currency?->conversion_rate ?: 1;
        $order->gmt = (int) ($input['gmt'] ?? 0);
        $order->customer_id = $customer->customer_id;
        $order->status_id = $status;
        $order->parent_id = 0;
        $order->service_type = $words ? Order::SERVICE_ARTICLE : Order::SERVICE_PAGES;
        $order->tariff_id = $deadline->deadline_id;
        $order->title = $input['title'];
        $order->academic_level_id = (int) ($deadline->academic_level_id ?: 0);
        $order->paper_type_id = (int) ($input['paper_type_id'] ?? 0);
        $order->paper_type_option = Services::ALL[$service] ?? $service;
        $order->topic_category_id = (int) ($input['discipline_id'] ?? 0);
        $order->paper_format_id = 0;
        $order->writer_category_id = (int) ($input['writer_category_id'] ?? 2);
        $order->writer_percent = WriterTiers::percent((int) ($input['writer_category_id'] ?? 2));
        $order->pages = $words ? 0 : (int) ($input['pages'] ?? 0);
        $order->spacing = ($input['spacing'] ?? 'double') === 'single' ? 'single' : 'double';
        $order->paper_details = $input['brief'];
        $order->words = $words ? (int) ($input['words'] ?? 0) : 0;
        $order->price_per_page = $words ? 0 : (float) $deadline->price_per_page;
        $order->price_per_word = $words ? (float) $deadline->price_per_page : 0;
        $order->hrs_customer = (int) $deadline->hrs_customer;
        $order->hrs_writer = (int) $deadline->hrs_writer;
        $order->customer_deadline = now()->addHours((int) $deadline->hrs_customer);
        $order->writer_deadline = now()->addHours((int) $deadline->hrs_writer);
        $pageWork = ! $words;
        $order->expert_proofreading = $pageWork && (bool) ($input['proofreading'] ?? false);
        $order->progressive_delivery = $pageWork && ((bool) ($input['progressive'] ?? false) || $quote->progressiveForced);
        $order->used_sources = $pageWork && (bool) ($input['sources'] ?? false);
        $order->writer_samples = $pageWork && (bool) ($input['samples'] ?? false);
        $order->vip_support = $pageWork && (bool) ($input['vip'] ?? false);
        $order->plagiarism_report = $pageWork && (bool) ($input['plagiarism'] ?? false);
        $order->draft_outline = $pageWork && (bool) ($input['outline'] ?? false);
        $order->is_complex_assignment = $pageWork && (bool) ($input['complex'] ?? false);
        $order->raw_cost = $quote->base + $quote->extras;
        $order->total = $total;
        $order->discount = $coupon ? (int) $coupon->coupon_value : 0;
        $order->discount_type = $coupon ? (int) $coupon->coupon_type : 0;
        $order->coupon_id = $coupon?->coupon_id;
        $order->writer_pay = $quote->writerPay;
        $order->editor_pay = $quote->editorPay;
        $order->order_type = 'article';
        $order->is_paid = $status === Order::NEW_PAID ? 1 : 0;
        $order->paid_at = $order->is_paid ? now() : null;
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        $this->notifier->staff(
            action: 'order',
            nodeType: $status === Order::FREE_INQUIRY ? 'new_inquiry' : 'new_order',
            nodeUrl: (string) $order->order_id,
            message: 'New '.($status === Order::FREE_INQUIRY ? 'enquiry' : 'order').' #'.$order->order_id,
            fromCustomerId: (int) $customer->customer_id,
        );

        $this->notifier->email('new_order_available', (string) $customer->email, [
            'order_id' => $order->order_id,
            'title' => $order->title,
            'username' => $customer->name,
            'total' => number_format($total, 2),
        ], 'We have your brief #'.$order->order_id);

        DeskEvent::dispatch('order.placed', (int) $order->order_id, ['status' => $status]);

        return $order;
    }

    private function coupon(mixed $code): ?Coupon
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        return Coupon::query()->where('coupon_code', $code)->where('active', 1)->first();
    }

    private function nextOrderId(): int
    {
        $step = (int) Configuration::read('ORDER_AUTOINCREMENT', config('legacy.order_id_step'));
        $step = max(1, $step);

        return (int) DB::transaction(function () use ($step) {
            $max = (int) DB::table('prox_order')->lockForUpdate()->max('order_id');

            if ($max === 0) {
                return (int) config('legacy.order_start_id');
            }

            return $max + $step;
        });
    }
}
