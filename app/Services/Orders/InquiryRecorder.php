<?php

namespace App\Services\Orders;

use App\Models\Configuration;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Order;
use App\Models\ProjectRequest;
use App\Services\Pricing\Quote;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InquiryRecorder
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function record(array $input, Quote $quote, ?Customer $customer, Deadline $deadline): ProjectRequest
    {
        $legacyOrderId = null;

        if ($customer && ! config('legacy.read_only')) {
            $legacyOrderId = $this->createOrder($input, $quote, $customer, $deadline);
        }

        return ProjectRequest::query()->create([
            'customer_id' => $customer?->customer_id,
            'service' => $input['service'],
            'name' => $customer?->name ?: ($input['name'] ?? ''),
            'email' => $customer?->email ?: ($input['email'] ?? ''),
            'title' => $input['title'],
            'brief' => $input['brief'],
            'deadline_id' => $deadline->deadline_id,
            'quote_total' => $quote->amountDue(),
            'quote' => $quote->toArray(),
            'legacy_order_id' => $legacyOrderId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function createOrder(array $input, Quote $quote, Customer $customer, Deadline $deadline): int
    {
        $service = (string) $input['service'];
        $words = Services::usesWords($service);
        $currency = Currency::query()->where('iso_code', 'USD')->first();

        $order = new Order;
        $order->order_id = $this->nextOrderId();
        $order->site_id = (int) config('legacy.site_id');
        $order->tracking_id = 'NW'.strtoupper(Str::random(8));
        $order->form_type_id = $words ? Order::SERVICE_ARTICLE : Order::SERVICE_PAGES;
        $order->form_type = 'professional';
        $order->currency_id = $currency?->currency_id;
        $order->currency_to_usd_rate = $currency?->conversion_rate ?: 1;
        $order->gmt = 0;
        $order->customer_id = $customer->customer_id;
        $order->status_id = Order::FREE_INQUIRY;
        $order->parent_id = 0;
        $order->service_type = $words ? Order::SERVICE_ARTICLE : Order::SERVICE_PAGES;
        $order->tariff_id = $deadline->deadline_id;
        $order->title = $input['title'];
        $order->academic_level_id = (int) ($deadline->academic_level_id ?: 0);
        $order->paper_type_id = 0;
        $order->topic_category_id = (int) ($input['discipline_id'] ?? 0);
        $order->paper_format_id = 0;
        $order->writer_category_id = (int) ($input['writer_category_id'] ?? 2);
        $order->writer_percent = WriterTiers::percent((int) ($input['writer_category_id'] ?? 2));
        $order->pages = $words ? 0 : (int) $input['pages'];
        $order->spacing = ($input['spacing'] ?? 'double') === 'single' ? 'single' : 'double';
        $order->paper_details = $input['brief'];
        $order->words = $words ? (int) $input['words'] : 0;
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
        $order->raw_cost = $quote->amountDue();
        $order->total = $quote->amountDue();
        $order->writer_pay = $quote->writerPay;
        $order->editor_pay = $quote->editorPay;
        $order->order_type = 'article';
        $order->date_add = now();
        $order->date_upd = now();
        $order->save();

        return (int) $order->order_id;
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
