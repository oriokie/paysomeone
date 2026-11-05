<?php

namespace App\Services\Payments;

use App\Events\DeskEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderTip;
use App\Models\Payment;
use App\Models\PaymentIntent;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(private readonly PaymentRecorder $recorder) {}

    public function blockedReason(): ?string
    {
        if (config('legacy.read_only')) {
            return 'Payments are paused on this preview so the live payment records stay unchanged.';
        }

        return null;
    }

    public function providerBlocked(): ?string
    {
        if ($reason = $this->blockedReason()) {
            return $reason;
        }

        if (config('legacy.safe_mode')) {
            return 'Outgoing payment calls are switched off (safe mode). Nothing was sent to M-Pesa, PayPal, Stripe, or Pesapal.';
        }

        return null;
    }

    public function intent(Customer $customer, string $provider, string $purpose, float $amount, ?Order $order, ?string $reference = null): PaymentIntent
    {
        return PaymentIntent::query()->create([
            'customer_id' => $customer->customer_id,
            'order_id' => $order?->order_id,
            'provider' => $provider,
            'purpose' => $purpose,
            'reference' => $reference ?: $provider.'_'.Str::lower(Str::random(20)),
            'amount' => $amount,
            'status' => 'pending',
        ]);
    }

    public function payWithWallet(Order $order, Customer $customer): string
    {
        if ($reason = $this->blockedReason()) {
            return $reason;
        }

        $amount = max(0, (float) $order->total - (float) $order->total_paid);
        if ($amount <= 0) {
            return 'This order is already paid.';
        }

        if ((float) $customer->wallet_balance + 0.001 < $amount) {
            return 'The wallet balance is lower than the amount due.';
        }

        $id = 'wallet_'.Str::lower(Str::random(16));
        $this->recorder->payOrder($order, 'wallet', $id, $amount);

        return 'Paid from the wallet.';
    }

    public function tipFromWallet(Order $order, Customer $customer, float $amount): string
    {
        if ($reason = $this->blockedReason()) {
            return $reason;
        }

        if ($amount < 1) {
            return 'Enter a tip of at least $1.';
        }

        $id = 'tip_'.Str::lower(Str::random(16));
        $this->recorder->debitWallet((int) $customer->customer_id, $amount, 'tip', $id, $order->currency_id);

        $tip = new OrderTip;
        $tip->order_id = $order->order_id;
        $tip->currency_id = $order->currency_id;
        $tip->transaction_id = $id;
        $tip->payment_method = 'wallet';
        $tip->amount = $amount;
        $tip->writer_tip_amount = $order->writer_id ? $amount : 0;
        $tip->editor_tip_amount = 0;
        $tip->date_add = now();
        $tip->save();

        $payment = new Payment;
        $payment->node_id = $order->order_id;
        $payment->node_type = 'tip';
        $payment->currency_id = $order->currency_id;
        $payment->conversion_rate = $order->currency_to_usd_rate ?: 1;
        $payment->payment_method = 'wallet';
        $payment->transaction_id = $id;
        $payment->amount = $amount;
        $payment->date_add = now();
        $payment->save();

        DeskEvent::dispatch('order.tip', (int) $order->order_id, ['amount' => $amount]);

        return 'Tip sent from the wallet.';
    }

    /**
     * Mark an intent paid and write the legacy payment rows. Safe to call twice.
     */
    public function settle(string $reference, string $transactionId, ?float $amount = null): bool
    {
        $applied = false;

        \Illuminate\Support\Facades\DB::transaction(function () use ($reference, $transactionId, $amount, &$applied) {
            $intent = PaymentIntent::query()->where('reference', $reference)->lockForUpdate()->first();
            if (! $intent || $intent->status === 'paid') {
                return;
            }

            if ($amount !== null && abs($amount - (float) $intent->amount) > 0.05) {
                throw new RuntimeException('The paid amount does not match the quote.');
            }

            $intent->status = 'paid';
            $intent->transaction_id = $transactionId;
            $intent->save();

            if ($intent->purpose === 'wallet') {
                $customer = Customer::query()->find($intent->customer_id);
                if ($customer) {
                    $applied = $this->recorder->creditWallet($customer, (float) $intent->amount, $intent->provider, $transactionId, null);
                }

                return;
            }

            $order = Order::query()->find($intent->order_id);
            if (! $order) {
                return;
            }

            if ($intent->purpose === 'tip') {
                $applied = $this->recordExternalTip($order, $intent, $transactionId);

                return;
            }

            $applied = $this->recorder->payOrder($order, $intent->provider, $transactionId, (float) $intent->amount);
        });

        return $applied;
    }

    private function recordExternalTip(Order $order, PaymentIntent $intent, string $transactionId): bool
    {
        $exists = Payment::query()->where('transaction_id', $transactionId)->where('node_type', 'tip')->exists();
        if ($exists) {
            return false;
        }

        $tip = new OrderTip;
        $tip->order_id = $order->order_id;
        $tip->currency_id = $order->currency_id;
        $tip->transaction_id = $transactionId;
        $tip->payment_method = $intent->provider;
        $tip->amount = $intent->amount;
        $tip->writer_tip_amount = $order->writer_id ? $intent->amount : 0;
        $tip->date_add = now();
        $tip->save();

        $payment = new Payment;
        $payment->node_id = $order->order_id;
        $payment->node_type = 'tip';
        $payment->currency_id = $order->currency_id;
        $payment->conversion_rate = $order->currency_to_usd_rate ?: 1;
        $payment->payment_method = $intent->provider;
        $payment->transaction_id = $transactionId;
        $payment->amount = $intent->amount;
        $payment->date_add = now();
        $payment->save();

        return true;
    }
}
