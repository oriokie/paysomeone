<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Same as Eloquent's provider, except a read-only legacy database
 * must not be updated just because someone logged in with an old hash.
 */
class LegacyUserProvider extends EloquentUserProvider
{
    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        if (config('legacy.read_only')) {
            return;
        }

        parent::rehashPasswordIfRequired($user, $credentials, $force);
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token)
    {
        if (! $user->getRememberTokenName()) {
            return;
        }

        parent::updateRememberToken($user, $token);
    }
}
