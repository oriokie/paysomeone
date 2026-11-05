<?php

namespace App\Jobs;

use App\Models\MailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTemplateEmail implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $template,
        public string $to,
        public array $data,
        public string $fallbackSubject,
        public string $category = 'orders',
        public bool $essential = false,
        public ?string $actionUrl = null,
    ) {}

    public function handle(): void
    {
        $stored = null;
        try {
            $stored = MailTemplate::query()->where('template', $this->template)->where('is_disabled', 0)->first();
        } catch (\Throwable) {
            $stored = null;
        }

        $subject = $stored?->subject ?: $this->fallbackSubject;
        $body = $stored?->message ?: $this->fallbackSubject."\n\n".json_encode($this->data);
        $subject = $this->fill($subject);
        $body = $this->fill($body);
        $lines = array_values(array_filter(preg_split("/\r\n|\n/", $body) ?: [], fn ($line) => trim($line) !== ''));

        app(\App\Services\Desk\OutboundMail::class)->send(
            $this->to,
            $subject,
            $subject,
            $lines === [] ? [$subject] : $lines,
            $this->actionUrl,
            'Open',
            $this->category,
            $this->essential,
        );
    }

    private function fill(string $text): string
    {
        $replace = [];
        foreach ($this->data as $key => $value) {
            $replace['{'.$key.'}'] = (string) $value;
            $replace['{$'.$key.'}'] = (string) $value;
        }

        return strtr($text, $replace);
    }
}
