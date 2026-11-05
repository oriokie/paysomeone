<?php

namespace App\Models;

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

    public const STATUSES = [
        self::WAITING_FOR_PAYMENT => 'Waiting for payment',
        self::DISPUTE => 'Dispute',
        self::CONFIRMATION_PENDING => 'Confirmation pending',
        self::WRITER_ASSIGNED => 'Writer assigned',
        self::WRITER_ACCEPTED => 'Writer accepted',
        self::DONE => 'Done',
        self::CANCELLED => 'Cancelled',
        self::FINISHED => 'Finished',
        self::DELIVERED => 'Delivered',
        self::NEW_PAID => 'New paid order',
        self::FREE_INQUIRY => 'Inquiry',
        self::REVISION => 'Revision',
        self::REPLY_PENDING => 'Reply pending',
        self::INQUIRY_PUBLISHED => 'Inquiry published',
        self::INQUIRY_WRITER_ACCEPTED => 'Inquiry writer accepted',
        self::ORDER_PUBLISHED => 'Published to writers',
    ];

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
        return self::STATUSES[(int) $this->status_id] ?? 'Status '.$this->status_id;
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
