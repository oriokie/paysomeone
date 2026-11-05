<?php

namespace App\Services\Orders;

use App\Events\DeskEvent;
use App\Models\Configuration;
use App\Models\ContentFeel;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Deadline;
use App\Models\Deliverable;
use App\Models\EnglishType;
use App\Models\Order;
use App\Models\PaperType;
use App\Models\TargetAudience;
use App\Models\WritingStyle;
use App\Models\WritingTone;
use App\Services\Desk\Notifier;
use App\Services\Pricing\Quote;
use App\Support\OrderDefaults;
use App\Support\OrderFormCatalog;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $pageWork = in_array($service, [Services::RESUMES, Services::EDITING, Services::REWRITING], true);
        $software = $service === Services::SOFTWARE;
        $currency = Currency::query()->where('iso_code', 'USD')->first();
        $coupon = $this->coupon($input['coupon_code'] ?? null);
        $intent = ($input['intent'] ?? 'pay') === 'inquiry' ? 'inquiry' : 'pay';
        $total = $quote->amountDue();
        [$paperTypeId, $paperTypeOption] = $this->documentType($input, $service);

        $status = Order::WAITING_FOR_PAYMENT;
        if ($total <= 0) {
            $status = Order::NEW_PAID;
        } elseif ($intent === 'inquiry') {
            $status = Order::FREE_INQUIRY;
        }

        $serviceType = match ($service) {
            Services::ARTICLES => Order::SERVICE_ARTICLE,
            Services::RESUMES => Order::SERVICE_RESUME,
            Services::SOFTWARE => Order::SERVICE_PROGRAMMING,
            default => Order::SERVICE_PAGES,
        };

        $order = new Order;
        $order->order_id = $this->nextOrderId();
        $order->site_id = (int) ($customer->site_id ?: config('legacy.site_id'));
        $order->tracking_id = 'NW'.strtoupper(Str::random(8));
        $order->form_type_id = $serviceType;
        $order->form_type = $service;
        $order->currency_id = $currency?->currency_id;
        $order->currency_to_usd_rate = $currency?->conversion_rate ?: 1;
        $order->gmt = (int) ($input['gmt'] ?? 0);
        $order->customer_id = $customer->customer_id;
        $order->status_id = $status;
        $order->parent_id = 0;
        $order->service_type = $serviceType;
        $order->tariff_id = $deadline->deadline_id;
        $order->title = $input['title'];
        $order->academic_level_id = OrderDefaults::ACADEMIC_LEVEL_ID;
        $order->paper_type_id = $paperTypeId;
        $order->paper_type_option = $paperTypeOption;
        $order->topic_category_id = OrderDefaults::TOPIC_CATEGORY_ID;
        $order->topic_category_option = OrderDefaults::TOPIC_CATEGORY_OPTION;
        $order->paper_format_id = OrderDefaults::PAPER_FORMAT_ID;
        $order->paper_format_option = OrderDefaults::PAPER_FORMAT_OPTION;
        $order->writer_category_id = (int) ($input['writer_category_id'] ?? 2);
        $order->writer_percent = WriterTiers::percent((int) ($input['writer_category_id'] ?? 2));
        $order->pages = $words ? 0 : (int) ($input['pages'] ?? 0);
        $order->slides = $pageWork ? (int) ($input['slides'] ?? 0) : 0;
        $order->charts = $pageWork ? (int) ($input['charts'] ?? 0) : 0;
        $order->sources = $pageWork ? (int) ($input['sources_count'] ?? 0) : 0;
        $order->spacing = ($input['spacing'] ?? OrderDefaults::SPACING) === 'single' ? 'single' : 'double';
        $order->paper_details = $input['brief'];
        $order->keywords = $words ? mb_substr(trim((string) ($input['keywords'] ?? '')), 0, 255) : null;
        $order->software = $software ? mb_substr(trim((string) ($input['software'] ?? '')), 0, 255) : null;
        $order->words = $words ? (int) ($input['words'] ?? 0) : 0;
        $order->english_type_id = $words ? $this->lookupId(EnglishType::class, 'english_type_id', $input['english_type_id'] ?? null) : null;
        $order->target_audience_id = $words ? $this->lookupId(TargetAudience::class, 'target_audience_id', $input['target_audience_id'] ?? null) : null;
        $order->writing_tone_id = $words ? $this->lookupId(WritingTone::class, 'writing_tone_id', $input['writing_tone_id'] ?? null) : null;
        $order->writing_style_id = $words ? $this->lookupId(WritingStyle::class, 'writing_style_id', $input['writing_style_id'] ?? null) : null;
        $order->content_feel_id = $words ? $this->lookupId(ContentFeel::class, 'content_feel_id', $input['content_feel_id'] ?? null) : null;
        $order->price_per_page = $words ? 0 : (float) $deadline->price_per_page;
        $order->price_per_word = $words ? (float) $deadline->price_per_page : 0;
        $order->hrs_customer = (int) $deadline->hrs_customer;
        $order->hrs_writer = (int) $deadline->hrs_writer;
        $order->customer_deadline = now()->addHours((int) $deadline->hrs_customer);
        $order->writer_deadline = now()->addHours((int) $deadline->hrs_writer);
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
        $order->order_type = $words ? 'article' : 'academic';
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

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: int, 1: string}
     */
    private function documentType(array $input, string $service): array
    {
        $catalog = app(OrderFormCatalog::class);
        $paperTypeId = (int) ($input['paper_type_id'] ?? 0);
        $paperType = $paperTypeId > 0 ? PaperType::query()->find($paperTypeId) : null;

        if ($paperType && ((bool) $paperType->is_academic_writing || $catalog->hidden((string) $paperType->title))) {
            throw ValidationException::withMessages([
                'paper_type_id' => 'That document type is not offered on a new order.',
            ]);
        }

        if ($service === Services::SOFTWARE) {
            $deliverableId = (int) ($input['deliverable_id'] ?? 0);
            $deliverable = $deliverableId > 0 ? Deliverable::query()->find($deliverableId) : null;
            $label = $deliverable?->name ?: ($paperType?->title ?: Services::ORDERABLE[$service]);

            return [$paperType ? (int) $paperType->paper_type_id : 0, (string) $label];
        }

        $label = $paperType?->title ?: (Services::ORDERABLE[$service] ?? $service);

        return [$paperType ? (int) $paperType->paper_type_id : 0, (string) $label];
    }

    /**
     * @param  class-string  $model
     */
    private function lookupId(string $model, string $key, mixed $id): ?int
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        return $model::query()->where($key, $id)->exists() ? $id : null;
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
