<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailPreferenceController extends Controller
{
    public function edit(Request $request, string $email): View
    {
        abort_unless($request->hasValidSignature(), 403);
        $row = NotificationPreference::query()->firstOrNew(['email' => strtolower($email)]);

        return view('pages.email-preferences', [
            'email' => strtolower($email),
            'orders' => (bool) ($row->orders ?? true),
            'messages' => (bool) ($row->messages ?? true),
            'payments' => (bool) ($row->payments ?? true),
        ]);
    }

    public function update(Request $request, string $email): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        NotificationPreference::query()->updateOrCreate(['email' => strtolower($email)], [
            'orders' => $request->boolean('orders'),
            'messages' => $request->boolean('messages'),
            'payments' => $request->boolean('payments'),
        ]);

        return back()->with('status', 'Preferences saved. Security messages and payment receipts still send.');
    }
}
