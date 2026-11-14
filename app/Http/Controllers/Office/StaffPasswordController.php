<?php

namespace App\Http\Controllers\Office;

use App\Models\Employee;
use App\Services\Desk\OutboundMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffPasswordController extends OfficeController
{
    public function requestForm(): View
    {
        return view('office.forgot');
    }

    public function send(Request $request, OutboundMail $mail): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);
        $staff = Employee::query()->where('email', $email)->whereIn('employee_group', [
            Employee::GROUP_ADMIN, Employee::GROUP_SUB_ADMIN, Employee::GROUP_EDITOR, Employee::GROUP_WRITER,
        ])->first();
        $link = null;
        if ($staff && ! config('legacy.read_only')) {
            $plain = Str::random(48);
            DB::table('app_staff_resets')->where('email', $email)->delete();
            DB::table('app_staff_resets')->insert(['email' => $email, 'token' => hash('sha256', $plain), 'created_at' => now()]);
            $link = url('/office/reset/'.$plain.'?email='.urlencode($email));
            $mail->send($email, 'Reset your office password', 'Reset your office password', [
                'Use the button to choose a new office password. The link works for 60 minutes.',
            ], $link, 'Choose a password', 'orders', true);
        }

        $message = 'If that email is on the staff list, a reset link is on its way.';
        if ($link && config('legacy.safe_mode')) {
            $message = 'Safe mode is on, so the email was not sent. Use this link on this preview only: '.$link;
        }

        return back()->with('status', $message);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('office.reset', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        if (config('legacy.read_only')) {
            return back()->withErrors(['email' => 'Password changes are paused on this preview.']);
        }
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $email = strtolower($data['email']);
        $row = DB::table('app_staff_resets')->where('email', $email)->first();
        if (! $row || ! hash_equals((string) $row->token, hash('sha256', $data['token']))) {
            return back()->withErrors(['email' => 'That reset link is invalid or has expired.']);
        }
        if (now()->diffInMinutes($row->created_at, true) > 60) {
            return back()->withErrors(['email' => 'That reset link is invalid or has expired.']);
        }
        $staff = Employee::query()->where('email', $email)->first();
        if (! $staff) {
            return back()->withErrors(['email' => 'That reset link is invalid or has expired.']);
        }
        $staff->password = Hash::make($data['password']);
        $staff->save();
        DB::table('app_staff_resets')->where('email', $email)->delete();

        return redirect()->route('office.login')->with('status', 'Password updated. Sign in with the new password.');
    }
}
