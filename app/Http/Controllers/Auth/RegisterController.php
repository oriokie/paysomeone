<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\LegacyWriteBlocked;
use App\Http\Controllers\Controller;
use App\Services\Accounts\CustomerRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CustomerRegistrar $registrar): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'terms' => ['accepted'],
            'subscribed' => ['nullable', 'boolean'],
        ]);

        try {
            $customer = $registrar->register($data);
        } catch (LegacyWriteBlocked) {
            return back()->withInput()->withErrors([
                'email' => 'New accounts are paused on this preview so the live customer list stays untouched. You can still sign in with an existing account.',
            ]);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account');
    }
}
