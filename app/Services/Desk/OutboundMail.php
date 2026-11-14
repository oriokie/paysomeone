<?php

namespace App\Services\Desk;

use App\Mail\BrandedMail;
use App\Models\MailLog;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class OutboundMail
{
    /**
     * @param  list<string>  $lines
     */
    public function send(
        string $to,
        string $subject,
        string $heading,
        array $lines,
        ?string $actionUrl = null,
        string $actionLabel = 'Open',
        string $category = 'orders',
        bool $essential = false,
    ): bool {
        $to = strtolower(trim($to));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (! $essential && ! NotificationPreference::allows($to, $category)) {
            $this->log($to, $subject, $category, 'skipped', 'Preference off');

            return false;
        }

        if (config('legacy.safe_mode')) {
            Log::info('SAFE_MODE suppressed an email.', ['to' => $to, 'subject' => $subject, 'category' => $category]);
            $this->log($to, $subject, $category, 'suppressed', 'Safe mode');

            return false;
        }

        try {
            Mail::to($to)->send(new BrandedMail(
                emailSubject: $subject,
                heading: $heading,
                lines: $lines,
                actionUrl: $actionUrl,
                actionLabel: $actionLabel,
                preferencesUrl: URL::signedRoute('email.preferences', ['email' => $to]),
                essential: $essential,
            ));
            $this->log($to, $subject, $category, 'queued', null);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Email failed.', ['to' => $to, 'subject' => $subject, 'error' => $exception->getMessage()]);
            $this->log($to, $subject, $category, 'failed', $exception->getMessage());

            return false;
        }
    }

    private function log(string $to, string $subject, string $category, string $status, ?string $detail): void
    {
        try {
            MailLog::query()->create([
                'email' => $to,
                'subject' => $subject,
                'category' => $category,
                'status' => $status,
                'detail' => $detail,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // A missing log table must not block the page that triggered the mail.
        }
    }
}
