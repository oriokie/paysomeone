<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStaffTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');
        if (! $staff) {
            return redirect()->route('office.login');
        }

        if ($staff->is_banned) {
            auth('staff')->logout();

            return redirect()->route('office.login')->withErrors(['email' => 'This account is suspended.']);
        }

        if (! config('office.two_factor_required', true)) {
            return $next($request);
        }

        if (! session('office.2fa')) {
            return redirect()->route($staff->two_factor_enabled ? 'office.challenge' : 'office.setup');
        }

        return $next($request);
    }
}
