<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRevision extends LegacyModel
{
    protected $table = 'prox_order_revision';

    protected $primaryKey = 'revision_id';

    protected function casts(): array
    {
        return [
            'free_revision' => 'boolean',
            'request_diff_writer' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(OrderFile::class, 'file_id', 'file_id');
    }
}
