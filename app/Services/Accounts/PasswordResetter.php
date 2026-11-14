<?php

namespace App\Services\Accounts;

use App\Exceptions\LegacyWriteBlocked;
use App\Models\Customer;
use App\Services\Desk\OutboundMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetter
{
    public function __construct(private readonly OutboundMail $mail) {}

    /**
     * Always returns a calm result. It does not reveal whether the email exists.
     *
     * @return array{sent: bool, paused: bool, link: string|null}
     */
    public function request(string $email): array
    {
        $email = strtolower(trim($email));

        if (config('legacy.read_only')) {
            return ['sent' => false, 'paused' => true, 'link' => null];
        }

        $customer = Customer::query()
            ->where('site_id', (int) config('legacy.site_id'))
            ->where('email', $email)
            ->first();

        if (! $customer || $customer->is_banned) {
            return ['sent' => false, 'paused' => false, 'link' => null];
        }

        $plain = Str::random(64);
        DB::table('app_password_resets')->where('email', $email)->delete();
        DB::table('app_password_resets')->insert([
            'email' => $email,
            'token' => hash('sha256', $plain),
            'created_at' => now(),
        ]);

        $link = url('/reset-password/'.$plain.'?email='.urlencode($email));
        $sent = $this->mail->send(
            $email,
            'Reset your password',
            'Reset your password',
            ['Use the button to choose a new password. The link works for 60 minutes. If you did not ask for this, you can ignore the email.'],
            $link,
            'Choose a password',
            'orders',
            true,
        );

        return [
            'sent' => $sent,
            'paused' => false,
            'link' => $sent ? null : $link,
        ];
    }

    public function reset(string $email, string $token, string $password): bool
    {
        if (config('legacy.read_only')) {
            throw new LegacyWriteBlocked('prox_customer');
        }

        $email = strtolower(trim($email));
        $row = DB::table('app_password_resets')->where('email', $email)->first();

        if (! $row || ! hash_equals((string) $row->token, hash('sha256', $token))) {
            return false;
        }

        if (now()->diffInMinutes($row->created_at, true) > 60) {
            return false;
        }

        $customer = Customer::query()
            ->where('site_id', (int) config('legacy.site_id'))
            ->where('email', $email)
            ->first();

        if (! $customer) {
            return false;
        }

        $customer->password = Hash::make($password);
        $customer->last_passwd_gen = now();
        $customer->save();

        DB::table('app_password_resets')->where('email', $email)->delete();

        return true;
    }
}
