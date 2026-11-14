<?php

namespace App\Http\Controllers\Office;

use App\Models\Employee;
use App\Models\RecoveryCode;
use App\Models\StaffDevice;
use App\Services\Office\Auditor;
use App\Services\Office\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SessionController extends OfficeController
{
    public function create(): View
    {
        return view('office.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $ok = Auth::guard('staff')->attempt([
            'email' => strtolower($data['email']),
            'password' => $data['password'],
        ], false);

        if (! $ok) {
            return back()->withErrors(['email' => 'Those details do not match a staff account.'])->onlyInput('email');
        }

        /** @var Employee $staff */
        $staff = Auth::guard('staff')->user();
        if ($staff->is_banned || ! in_array((int) $staff->employee_group, [
            Employee::GROUP_ADMIN, Employee::GROUP_SUB_ADMIN, Employee::GROUP_EDITOR, Employee::GROUP_WRITER,
        ], true)) {
            Auth::guard('staff')->logout();

            return back()->withErrors(['email' => 'This account cannot open the office.']);
        }

        $request->session()->regenerate();
        try {
            $staff->last_login = now();
            $staff->save();
        } catch (\App\Exceptions\LegacyWriteBlocked) {
            // A read-only preview can still sign in. The stored last-login time stays as it is.
        }
        StaffDevice::query()->create([
            'employee_id' => $staff->employee_id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
        Auditor::write($staff, 'auth.login');
        app(\App\Services\Desk\OutboundMail::class)->send(
            (string) $staff->email,
            'New sign-in to the office',
            'New sign-in to the office',
            ['A sign-in just used your staff password from '.$request->ip().'. If this was not you, reset the password.'],
            route('office.forgot'),
            'Reset password',
            'orders',
            true,
        );
        session()->forget('office.2fa');

        return redirect()->route($staff->two_factor_enabled ? 'office.challenge' : 'office.setup');
    }

    public function setup(Request $request): View
    {
        $staff = $this->staff($request);
        $secret = session('office.setup_secret');
        if (! $secret) {
            $secret = Totp::secret();
            session(['office.setup_secret' => $secret]);
        }

        return view('office.security', [
            'mode' => 'setup',
            'secret' => $secret,
            'uri' => Totp::uri($secret, (string) $staff->email, (string) config('app.name')),
            'codes' => session('office.recovery_plain', []),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $secret = (string) session('office.setup_secret');
        $data = $request->validate(['code' => ['required', 'string']]);
        if ($secret === '' || ! Totp::verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'That code does not match. Check the time on your phone and try again.']);
        }

        $plain = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();
        RecoveryCode::query()->where('employee_id', $staff->employee_id)->delete();
        foreach ($plain as $code) {
            RecoveryCode::query()->create([
                'employee_id' => $staff->employee_id,
                'code_hash' => hash('sha256', $code),
                'created_at' => now(),
            ]);
        }

        $staff->two_factor_enabled = 1;
        $staff->two_factor_type = 'google';
        $staff->two_factor_gsecret = $secret;
        $staff->save();
        session()->forget('office.setup_secret');
        session([
            'office.2fa' => true,
            'office.seen' => time(),
            'office.recovery_plain' => $plain,
        ]);
        Auditor::write($staff, 'auth.2fa_enabled');

        return redirect()->route('office.home')->with('status', 'Two-factor sign-in is on. Save the recovery codes shown once on this page.');
    }

    public function challenge(Request $request): View
    {
        return view('office.security', ['mode' => 'challenge', 'secret' => null, 'uri' => null, 'codes' => []]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);
        $data = $request->validate(['code' => ['required', 'string']]);
        $code = strtoupper(preg_replace('/\s+/', '', $data['code']) ?? '');

        $totp = Totp::verify((string) $staff->two_factor_gsecret, $data['code']);
        $recovery = false;
        if (! $totp) {
            $row = RecoveryCode::query()
                ->where('employee_id', $staff->employee_id)
                ->whereNull('used_at')
                ->where('code_hash', hash('sha256', $code))
                ->first();
            if ($row) {
                $row->used_at = now();
                $row->save();
                $recovery = true;
            }
        }
        if (! $totp && ! $recovery) {
            return back()->withErrors(['code' => 'That code is not valid.']);
        }

        session(['office.2fa' => true, 'office.seen' => time()]);
        Auditor::write($staff, $recovery ? 'auth.recovery' : 'auth.2fa');

        return redirect()->route('office.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $staff = $request->user('staff');
        if ($staff) {
            Auditor::write($staff, 'auth.logout');
        }
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('office.login');
    }
}
