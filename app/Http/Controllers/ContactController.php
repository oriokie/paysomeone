<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\Outbound\MailGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, MailGateway $mail): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        ContactMessage::query()->create($data + ['created_at' => now()]);

        $sent = $mail->send(
            (string) config('mail.from.address'),
            'New message from '.$data['name'],
            $data['name'].' ('.$data['email'].')'
                .($data['company'] ? ', '.$data['company'] : '')
                ."\n\n".$data['message'],
        );

        $note = $sent
            ? 'Message received. We will reply by email.'
            : 'Message received and saved. Email delivery is paused on this preview, so the note is also in the application log.';

        return back()->with('status', $note);
    }
}
