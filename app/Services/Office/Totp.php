<?php

namespace App\Services\Office;

/**
 * RFC 6238 TOTP, 6 digits, 30 second step, SHA-1.
 * The secret is base32, stored on prox_employee.two_factor_gsecret.
 */
class Totp
{
    public static function secret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $secret .= $alphabet[bindec($chunk)];
        }

        return $secret;
    }

    public static function at(string $secret, ?int $time = null): string
    {
        $slice = (int) floor(($time ?? time()) / 30);

        return self::code($secret, $slice);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $slice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $slice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display form of a secret. Groups of four are easier to type.
     * The stored secret is not changed.
     */
    public static function grouped(string $secret): string
    {
        $secret = preg_replace('/\s+/', '', $secret) ?? '';

        return implode(' ', str_split($secret, 4));
    }

    public static function uri(string $secret, string $email, string $issuer): string
    {
        $secret = self::canonical($secret);
        $issuer = self::issuer($issuer);
        $account = trim($email);
        $label = rawurlencode($issuer).':'.rawurlencode($account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    /**
     * Uppercase base32, no padding, no spaces. A valid secret is unchanged.
     */
    private static function canonical(string $secret): string
    {
        $secret = strtoupper($secret);
        $secret = str_replace([' ', "\t", "\r", "\n", '-', '='], '', $secret);

        return preg_replace('/[^A-Z2-7]/', '', $secret) ?? '';
    }

    /**
     * Authenticator apps reject a colon in the issuer (it is the label
     * separator) and path or query characters that can corrupt the secret
     * once the URI is decoded. Letters, digits, spaces, and ._- stay.
     */
    private static function issuer(string $issuer): string
    {
        $issuer = preg_replace('/[^A-Za-z0-9 ._-]+/', ' ', $issuer) ?? '';
        $issuer = trim(preg_replace('/\s+/', ' ', $issuer) ?? '');

        return $issuer !== '' ? $issuer : 'Office';
    }

    private static function code(string $secret, int $slice): string
    {
        $key = self::decode($secret);
        $binary = pack('N*', 0).pack('N*', $slice);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private static function decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $decoded = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
