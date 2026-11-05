<?php

namespace App\Support\Office;

/**
 * Payment modules shipped with the current Proxim office.
 * Secrets stay in the environment. This list is only names and which env key to mask.
 */
class Gateways
{
    public const ALL = [
        'mpesa' => ['label' => 'M-Pesa', 'env' => 'MPESA_CONSUMER_KEY'],
        'paypal' => ['label' => 'PayPal', 'env' => 'PAYPAL_CLIENT_ID'],
        'stripe' => ['label' => 'Stripe', 'env' => 'STRIPE_SECRET'],
        'pesapal' => ['label' => 'Pesapal', 'env' => 'PESAPAL_CONSUMER_KEY'],
        'paystack' => ['label' => 'Paystack', 'env' => 'PAYSTACK_SECRET'],
        'flutterwave' => ['label' => 'Flutterwave', 'env' => 'FLUTTERWAVE_SECRET'],
        'skrill' => ['label' => 'Skrill', 'env' => 'SKRILL_MERCHANT'],
        'authorize' => ['label' => 'Authorize.net', 'env' => 'AUTHORIZE_LOGIN'],
        'two_checkout' => ['label' => '2Checkout', 'env' => 'TWOCHECKOUT_SELLER'],
        'peach' => ['label' => 'Peach Payments', 'env' => 'PEACH_ENTITY'],
        'intasend' => ['label' => 'IntaSend', 'env' => 'INTASEND_KEY'],
        'fasterpay' => ['label' => 'FasterPay', 'env' => 'FASTERPAY_KEY'],
        'eazzypay' => ['label' => 'EazzyPay', 'env' => 'EAZZYPAY_KEY'],
        'jenga' => ['label' => 'Jenga', 'env' => 'JENGA_KEY'],
        'safepay' => ['label' => 'Safepay', 'env' => 'SAFEPAY_KEY'],
        'securetrading' => ['label' => 'Secure Trading', 'env' => 'SECURETRADING_SITE'],
        'cybersource' => ['label' => 'Cybersource', 'env' => 'CYBERSOURCE_KEY'],
        't1payments' => ['label' => 'T1 Payments', 'env' => 'T1_KEY'],
    ];

    public static function mask(string $envKey): string
    {
        $value = (string) env($envKey, '');

        return $value === '' ? 'Not set' : '••••'.substr($value, -4);
    }
}
