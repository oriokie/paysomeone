<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestOrder extends LegacyModel
{
    protected $table = 'prox_payment_request_order';

    protected $primaryKey = 'node_id';

    public $timestamps = false;

    public function request(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id', 'payment_request_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
