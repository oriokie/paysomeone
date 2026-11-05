<?php

namespace App\Services\Accounts;

use App\Exceptions\LegacyWriteBlocked;
use App\Models\Customer;
use App\Services\Desk\OutboundMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerRegistrar
{
    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null, subscribed?: bool}  $input
     */
    public function __construct(private readonly OutboundMail $mail) {}

    public function register(array $input): Customer
    {
        if (config('legacy.read_only')) {
            throw new LegacyWriteBlocked('prox_customer');
        }

        $email = strtolower(trim($input['email']));
        $siteId = (int) config('legacy.site_id');

        $exists = Customer::query()->where('site_id', $siteId)->where('email', $email)->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'An account with that email already exists. Sign in instead.',
            ]);
        }

        $customer = new Customer;
        $customer->customer_id = $this->nextId();
        $customer->google_analytics_id = '';
        $customer->email = $email;
        $customer->name = trim($input['name']);
        $customer->phone = $input['phone'] ?? null;
        $customer->country_id = 0;
        $customer->site_id = $siteId;
        $customer->password = Hash::make($input['password']);
        $customer->secure_key = md5(Str::uuid()->toString());
        $customer->policy_accepted = 1;
        $customer->policy_accepted_at = now();
        $customer->last_passwd_gen = now();
        $referrer = (int) ($input['referrer_id'] ?? 0);
        if ($referrer > 0 && Customer::query()->where('customer_id', $referrer)->exists()) {
            $customer->referrer_id = $referrer;
        }
        $customer->is_subscribed = (bool) ($input['subscribed'] ?? false);
        $customer->reg_date = now();
        $customer->save();
        $this->mail->send($customer->email, 'Your account is open', 'Your account is open', [
            'You can place a brief whenever you are ready. We do not take student or academic work.',
        ], route('account'), 'Open your account', 'orders', true);

        return $customer;
    }

    public function nextId(): int
    {
        return (int) DB::transaction(function () {
            $max = (int) DB::table('prox_customer')->lockForUpdate()->max('customer_id');

            if ($max === 0) {
                return (int) config('legacy.customer_start_id');
            }

            return $max + 2;
        });
    }
}
