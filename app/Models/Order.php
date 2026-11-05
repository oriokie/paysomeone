<?php

namespace App\Models;

use App\Support\OrderStatuses;
use App\Support\Services;
use App\Support\WriterTiers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends LegacyModel
{
    public const WAITING_FOR_PAYMENT = 0;

    public const DISPUTE = 1;

    public const CONFIRMATION_PENDING = 2;

    public const WRITER_ASSIGNED = 6;

    public const WRITER_ACCEPTED = 7;

    public const DONE = 8;

    public const CANCELLED = 9;

    public const FINISHED = 10;

    public const DELIVERED = 11;

    public const NEW_PAID = 12;

    public const FREE_INQUIRY = 13;

    public const REVISION = 14;

    public const REPLY_PENDING = 15;

    public const INQUIRY_PUBLISHED = 16;

    public const INQUIRY_WRITER_ACCEPTED = 17;

    public const ORDER_PUBLISHED = 19;

    public const SERVICE_PAGES = 37;

    public const SERVICE_RESUME = 49;

    public const SERVICE_CALCULATIONS = 50;

    public const SERVICE_PROGRAMMING = 51;

    public const SERVICE_ARTICLE = 52;

    public const STATUSES = OrderStatuses::TITLES;

    protected $table = 'prox_order';

    protected $primaryKey = 'order_id';

    protected function casts(): array
    {
        return [
            'pages' => 'float',
            'total' => 'float',
            'raw_cost' => 'float',
            'price_per_page' => 'float',
            'price_per_word' => 'float',
            'writer_pay' => 'float',
            'editor_pay' => 'float',
            'is_paid' => 'boolean',
            'writer_samples' => 'boolean',
            'used_sources' => 'boolean',
            'expert_proofreading' => 'boolean',
            'progressive_delivery' => 'boolean',
            'vip_support' => 'boolean',
            'plagiarism_report' => 'boolean',
            'draft_outline' => 'boolean',
            'is_complex_assignment' => 'boolean',
            'customer_deadline' => 'datetime',
            'writer_deadline' => 'datetime',
            'date_add' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return OrderStatuses::title((int) $this->status_id);
    }

    public function statusDescription(): string
    {
        return OrderStatuses::description((int) $this->status_id);
    }

    /**
     * What the customer sees. A legacy page order with an academic level
     * keeps the current site's "Academic Writing" label. New professional
     * orders use the service they chose, even when service_type is 37.
     */
    public function serviceLabel(): string
    {
        if ((int) $this->academic_level_id > 0 && (int) $this->service_type === self::SERVICE_PAGES) {
            return OrderStatuses::serviceTitle(self::SERVICE_PAGES);
        }

        $form = (string) $this->form_type;
        if (isset(Services::ORDERABLE[$form])) {
            return Services::ORDERABLE[$form];
        }

        return OrderStatuses::serviceTitle((int) $this->service_type);
    }

    public function writerCategoryLabel(): string
    {
        return WriterTiers::TIERS[(int) $this->writer_category_id]['label'] ?? 'Best available';
    }

    public function showsAcademicRecord(): bool
    {
        return (int) $this->academic_level_id > 0
            || ((int) $this->paper_format_id > 0 && (int) $this->paper_format_id !== 4)
            || ((int) $this->topic_category_id > 0 && (int) $this->topic_category_id !== 52);
    }

    public function scopeForCustomerTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'unpaid' => $query->where('status_id', self::WAITING_FOR_PAYMENT),
            'finished' => $query->where('status_id', self::FINISHED),
            'cancelled' => $query->where('status_id', self::CANCELLED),
            default => $query->where(function (Builder $inner) {
                $inner->whereIn('status_id', OrderStatuses::RECENT)
                    ->orWhere(function (Builder $week) {
                        $week->where('date_add', '>=', now()->subWeek())
                            ->where('status_id', '!=', self::CANCELLED);
                    });
            }),
        };
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id', 'site_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'currency_id');
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'academic_level_id', 'academic_level_id');
    }

    public function paperType(): BelongsTo
    {
        return $this->belongsTo(PaperType::class, 'paper_type_id', 'paper_type_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class, 'topic_category_id', 'discipline_id');
    }

    public function paperFormat(): BelongsTo
    {
        return $this->belongsTo(PaperFormat::class, 'paper_format_id', 'paper_format_id');
    }

    public function englishType(): BelongsTo
    {
        return $this->belongsTo(EnglishType::class, 'english_type_id', 'english_type_id');
    }

    public function targetAudience(): BelongsTo
    {
        return $this->belongsTo(TargetAudience::class, 'target_audience_id', 'target_audience_id');
    }

    public function writingTone(): BelongsTo
    {
        return $this->belongsTo(WritingTone::class, 'writing_tone_id', 'writing_tone_id');
    }

    public function writingStyle(): BelongsTo
    {
        return $this->belongsTo(WritingStyle::class, 'writing_style_id', 'writing_style_id');
    }

    public function contentFeel(): BelongsTo
    {
        return $this->belongsTo(ContentFeel::class, 'content_feel_id', 'content_feel_id');
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class, 'tariff_id', 'deadline_id');
    }

    public function writer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'writer_id', 'employee_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'editor_id', 'employee_id');
    }

    public function requestedWriter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_writer_id', 'employee_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'coupon_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class, 'order_id', 'order_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_id', 'order_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderRevision::class, 'order_id', 'order_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(OrderRating::class, 'order_id', 'order_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(OrderBid::class, 'order_id', 'order_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderEmployee::class, 'order_id', 'order_id');
    }

    public function metas(): HasMany
    {
        return $this->hasMany(OrderMeta::class, 'order_id', 'order_id');
    }

    public function tips(): HasMany
    {
        return $this->hasMany(OrderTip::class, 'order_id', 'order_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(OrderPdSchedule::class, 'order_id', 'order_id');
    }

    public function additionalServices(): HasMany
    {
        return $this->hasMany(AdditionalService::class, 'order_id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'node_id', 'order_id')->where('node_type', 'order');
    }
}
