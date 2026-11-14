<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\LegacyWriteBlocked;
use App\Http\Controllers\Controller;
use App\Services\Accounts\PasswordResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot');
    }

    public function send(Request $request, PasswordResetter $resets): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $result = $resets->request($data['email']);

        if ($result['paused']) {
            return back()->withErrors([
                'email' => 'Password changes are paused on this preview. Reset your password on the current website, then sign in here with the same password.',
            ]);
        }

        $message = 'If that email has an account, a reset link is on its way.';
        if ($result['link'] && ! app()->environment('production')) {
            $message = 'Safe mode is on, so the email was not sent. Use this link on this preview only: '.$result['link'];
        }

        return back()->with('status', $message);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, PasswordResetter $resets): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        try {
            $ok = $resets->reset($data['email'], $data['token'], $data['password']);
        } catch (LegacyWriteBlocked) {
            return back()->withErrors([
                'email' => 'Password changes are paused on this preview.',
            ]);
        }

        if (! $ok) {
            return back()->withErrors(['email' => 'That reset link is invalid or has expired.']);
        }

        return redirect()->route('login')->with('status', 'Password updated. You can sign in.');
    }
}
