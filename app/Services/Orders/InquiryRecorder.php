<?php

namespace App\Services\Orders;

use App\Models\Customer;
use App\Models\Deadline;
use App\Models\ProjectRequest;
use App\Services\Pricing\Quote;

class InquiryRecorder
{
    public function __construct(private readonly OrderPlacer $placer) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function record(array $input, Quote $quote, ?Customer $customer, Deadline $deadline): ProjectRequest
    {
        $legacyOrderId = null;

        if ($customer && ! config('legacy.read_only')) {
            $legacyOrderId = $this->placer->place($input, $quote, $customer, $deadline)->order_id;
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
}
