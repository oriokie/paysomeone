<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Contracts\Hashing\Hasher;

/**
 * Checks the password formats already stored for customers and staff,
 * and writes new passwords as bcrypt.
 *
 * bcrypt is the format the current site already prefers. Its login
 * accepts a bcrypt hash with password_verify(), then the older
 * 9-character salt + SHA1, then a plain MD5. A password we rehash
 * to bcrypt still works on the current site.
 *
 * Argon2 is not used. The current site would only accept it when its
 * PHP build understands Argon2, and that is not guaranteed.
 */
class LegacyHasher implements Hasher
{
    public function __construct(private readonly int $bcryptRounds = 12) {}

    public function info($hashedValue): array
    {
        if (! is_string($hashedValue)) {
            return ['algo' => null, 'algoName' => 'unknown', 'options' => []];
        }

        $info = password_get_info($hashedValue);

        if (($info['algo'] ?? 0) !== 0) {
            return $info;
        }

        if ($this->looksLikeSaltedSha1($hashedValue)) {
            return ['algo' => null, 'algoName' => 'salted-sha1', 'options' => []];
        }

        if ($this->looksLikeMd5($hashedValue)) {
            return ['algo' => null, 'algoName' => 'md5', 'options' => []];
        }

        return ['algo' => null, 'algoName' => 'unknown', 'options' => []];
    }

    public function make(#[\SensitiveParameter] $value, array $options = []): string
    {
        $rounds = (int) ($options['rounds'] ?? $this->bcryptRounds);

        return (string) password_hash((string) $value, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }

    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        if (! is_string($value) || ! is_string($hashedValue) || $hashedValue === '') {
            return false;
        }

        $info = password_get_info($hashedValue);

        if (($info['algo'] ?? 0) !== 0) {
            return password_verify($value, $hashedValue);
        }

        if ($this->looksLikeMd5($hashedValue)) {
            return hash_equals($hashedValue, md5($value));
        }

        if ($this->looksLikeSaltedSha1($hashedValue)) {
            return hash_equals($hashedValue, $this->saltedSha1($value, substr($hashedValue, 0, 9)));
        }

        return false;
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue) || $hashedValue === '') {
            return true;
        }

        $info = password_get_info($hashedValue);

        if (($info['algo'] ?? 0) === 0) {
            return true;
        }

        $rounds = (int) ($options['rounds'] ?? $this->bcryptRounds);

        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }

    public function saltedSha1(string $password, ?string $salt = null): string
    {
        if ($salt === null) {
            $salt = substr(md5(uniqid((string) random_int(0, PHP_INT_MAX), true)), 0, 9);
        } else {
            $salt = substr($salt, 0, 9);
        }

        return $salt.sha1($password.$salt);
    }

    private function looksLikeMd5(string $hash): bool
    {
        return strlen($hash) === 32 && ctype_xdigit($hash);
    }

    private function looksLikeSaltedSha1(string $hash): bool
    {
        return strlen($hash) === 49;
    }
}
