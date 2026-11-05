<?php

namespace App\Http\Middleware;

use App\Models\OfficeSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouchStaffSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) OfficeSetting::get('session_minutes', '30');
        $minutes = max(5, $minutes);
        $seen = (int) session('office.seen', 0);
        if ($seen && (time() - $seen) > ($minutes * 60)) {
            auth('staff')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('office.login')->withErrors(['email' => 'Your office session timed out. Sign in again.']);
        }
        session(['office.seen' => time()]);

        return $next($request);
    }
}
