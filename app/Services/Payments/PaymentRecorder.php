<?php

namespace App\Services\Payments;

use App\Events\DeskEvent;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\WalletTransaction;
use App\Services\Desk\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentRecorder
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Apply a successful charge once. A repeated transaction id is ignored.
     */
    public function payOrder(Order $order, string $method, string $transactionId, float $amount): bool
    {
        $applied = false;

        DB::transaction(function () use ($order, $method, $transactionId, $amount, &$applied) {
            $existing = Payment::query()
                ->where('node_type', 'order')
                ->where('node_id', $order->order_id)
                ->where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                return;
            }

            $fresh = Order::query()->where('order_id', $order->order_id)->lockForUpdate()->first();
            if (! $fresh) {
                return;
            }

            $payment = new Payment;
            $payment->node_id = $fresh->order_id;
            $payment->node_type = 'order';
            $payment->currency_id = $fresh->currency_id;
            $payment->conversion_rate = $fresh->currency_to_usd_rate ?: 1;
            $payment->payment_method = $method;
            $payment->transaction_id = $transactionId;
            $payment->amount = $amount;
            $payment->date_add = now();
            $payment->save();

            $wasUnpaid = ! $fresh->is_paid;
            $fresh->payment_method = $method;
            $fresh->total_paid = (float) $fresh->total_paid + $amount;
            if ($wasUnpaid && (float) $fresh->total_paid + 0.001 >= (float) $fresh->total) {
                $fresh->is_paid = 1;
                $fresh->paid_at = now();
                if (in_array((int) $fresh->status_id, [Order::WAITING_FOR_PAYMENT, Order::FREE_INQUIRY, Order::CONFIRMATION_PENDING], true)) {
                    $configured = (int) Configuration::read('NEW_ORDER_STATUS', 0);
                    $fresh->status_id = $fresh->progressive_delivery
                        ? Order::NEW_PAID
                        : ($configured > 0 ? $configured : Order::ORDER_PUBLISHED);
                }
            }
            $fresh->date_upd = now();
            $fresh->save();

            if ($method === 'wallet') {
                $this->debitWallet((int) $fresh->customer_id, $amount, 'order', $transactionId, $fresh->currency_id);
            }

            if ($wasUnpaid) {
                $customer = Customer::query()->find($fresh->customer_id);
                if ($customer) {
                    $customer->total_spent = (float) $customer->total_spent + $amount;
                    $customer->total_orders = (int) $customer->total_orders + 1;
                    $this->creditReferrer($customer);
                    $customer->save();
                    $this->notifier->customer((int) $customer->customer_id, 'order', 'new_payment', (string) $fresh->order_id, 'Payment received for order #'.$fresh->order_id);
                    $this->notifier->email('order_payment', (string) $customer->email, [
                        'order_id' => $fresh->order_id,
                        'title' => $fresh->title,
                        'paid_amount' => number_format($amount, 2),
                        'payment_method' => $method,
                        'username' => $customer->name,
                    ], 'Payment received for order #'.$fresh->order_id, 'payments', true, route('desk.order', $fresh));
                }
            }

            $applied = true;
            DeskEvent::dispatch('order.paid', (int) $fresh->order_id, ['method' => $method, 'transaction' => $transactionId]);
        });

        return $applied;
    }

    public function creditWallet(Customer $customer, float $amount, string $method, string $transactionId, ?int $currencyId): bool
    {
        $applied = false;

        DB::transaction(function () use ($customer, $amount, $method, $transactionId, $currencyId, &$applied) {
            $exists = WalletTransaction::query()->where('transaction_id', $transactionId)->lockForUpdate()->exists();
            if ($exists) {
                return;
            }

            $fresh = Customer::query()->where('customer_id', $customer->customer_id)->lockForUpdate()->first();
            if (! $fresh) {
                return;
            }

            $tx = new WalletTransaction;
            $tx->customer_id = $fresh->customer_id;
            $tx->currency_id = $currencyId;
            $tx->transaction_id = $transactionId;
            $tx->source = 'deposit';
            $tx->payment_method = $method;
            $tx->amount = $amount;
            $tx->date_add = now();
            $tx->save();

            $fresh->wallet_balance = (float) $fresh->wallet_balance + $amount;
            $fresh->save();
            $applied = true;
        });

        return $applied;
    }

    public function debitWallet(int $customerId, float $amount, string $source, string $transactionId, ?int $currencyId): void
    {
        $exists = WalletTransaction::query()->where('transaction_id', $transactionId)->lockForUpdate()->exists();
        if ($exists) {
            return;
        }

        $customer = Customer::query()->where('customer_id', $customerId)->lockForUpdate()->first();
        if (! $customer || (float) $customer->wallet_balance + 0.001 < $amount) {
            throw new \RuntimeException('The wallet does not have enough for this payment.');
        }

        $tx = new WalletTransaction;
        $tx->customer_id = $customer->customer_id;
        $tx->currency_id = $currencyId;
        $tx->transaction_id = $transactionId;
        $tx->source = $source;
        $tx->payment_method = 'wallet';
        $tx->amount = $amount;
        $tx->date_add = now();
        $tx->save();

        $customer->wallet_balance = (float) $customer->wallet_balance - $amount;
        $customer->save();
    }

    private function creditReferrer(Customer $customer): void
    {
        if (! $customer->referrer_id || $customer->affiliate_used) {
            return;
        }

        $exists = Referral::query()
            ->where('referrer_id', $customer->referrer_id)
            ->where('referree_id', $customer->customer_id)
            ->exists();

        if (! $exists) {
            $referral = new Referral;
            $referral->referrer_id = $customer->referrer_id;
            $referral->referree_id = $customer->customer_id;
            $referral->code = strtoupper(Str::random(8));
            $referral->amount = 10;
            $referral->referree_used = 1;
            $referral->date_add = now();
            $referral->date_upd = now();
            $referral->save();

            $referrer = Customer::query()->find($customer->referrer_id);
            if ($referrer) {
                $referrer->affiliate_balance = (float) $referrer->affiliate_balance + 10;
                $referrer->save();
            }
        }

        $customer->affiliate_used = 1;
    }
}
