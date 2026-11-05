<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends LegacyModel
{
    public const ACTION_ORDER = 'order';

    public const ACTION_MESSAGE = 'message';

    public const ACTION_REPLY = 'reply';

    public const ACTION_FILE = 'file';

    public const TYPE_NEW_ORDER = 'new_order';

    public const TYPE_NEW_INQUIRY = 'new_inquiry';

    public const TYPE_PAYMENT = 'new_payment';

    public const TYPE_MESSAGE = 'new_message';

    public const TYPE_REPLY = 'new_reply';

    protected $table = 'prox_notification';

    protected $primaryKey = 'notification_id';

    protected function casts(): array
    {
        return [
            'is_seen' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'to_user_id', 'customer_id');
    }
}
