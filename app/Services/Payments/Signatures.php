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
}
