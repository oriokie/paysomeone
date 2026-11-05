<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $email = strtolower($data['email']);
        $customer = Customer::query()
            ->where('site_id', (int) config('legacy.site_id'))
            ->where('email', $email)
            ->first();

        $ok = Auth::guard('customer')->attempt([
            'email' => $email,
            'password' => $data['password'],
            'site_id' => (int) config('legacy.site_id'),
        ], false);

        if (! $ok) {
            return back()->withErrors(['email' => 'Those details do not match an account.'])->onlyInput('email');
        }

        if ($customer?->is_banned) {
            Auth::guard('customer')->logout();

            return back()->withErrors(['email' => 'This account is closed. Contact the studio if that is a surprise.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
