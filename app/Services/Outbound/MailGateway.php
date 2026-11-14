<?php

namespace App\Services\Outbound;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailGateway
{
    public function send(string $to, string $subject, string $body): bool
    {
        if (config('legacy.safe_mode')) {
            Log::info('SAFE_MODE suppressed an email.', ['to' => $to, 'subject' => $subject]);

            return false;
        }

        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });

        return true;
    }
}
