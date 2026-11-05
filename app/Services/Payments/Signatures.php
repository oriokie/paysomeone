<?php

namespace App\Services\Payments;

class Signatures
{
    public static function stripe(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        if ($header === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $item) {
            if (! str_contains($item, '=')) {
                continue;
            }
            [$key, $value] = explode('=', trim($item), 2);
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp === null || $signatures === [] || abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public static function hmac(string $payload, string $provided, string $secret): bool
    {
        if ($secret === '' || $provided === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $secret), $provided);
    }

    /**
     * PayPal webhook verification. The certificate must be PayPal's, or a PEM
     * supplied only by our own config during a local test.
     *
     * @param  array<string, string>  $headers
     */
    public static function paypal(string $payload, array $headers, string $webhookId, ?string $certificate = null): bool
    {
        $id = $headers['paypal-transmission-id'] ?? '';
        $time = $headers['paypal-transmission-time'] ?? '';
        $signature = $headers['paypal-transmission-sig'] ?? '';
        $url = $headers['paypal-cert-url'] ?? '';
        if ($id === '' || $time === '' || $signature === '' || $webhookId === '') {
            return false;
        }

        if ($certificate === null || $certificate === '') {
            if (! preg_match('#^https://([a-z0-9-]+\.)*paypal\.com/#i', $url)) {
                return false;
            }
            $certificate = \Illuminate\Support\Facades\Http::timeout(10)->get($url)->body();
        }

        $public = openssl_pkey_get_public($certificate);
        if ($public === false) {
            return false;
        }

        $message = $id.'|'.$time.'|'.$webhookId.'|'.sprintf('%u', crc32($payload));
        $verified = openssl_verify($message, base64_decode($signature, true) ?: '', $public, OPENSSL_ALGO_SHA256);

        return $verified === 1;
    }
}
