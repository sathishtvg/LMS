<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CourseCompleted extends Notification
{
    use Queueable;

    public function __construct(public array $payload) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $p = $this->payload;

        $m = (new MailMessage)->subject($p['subject'] ?? '[LMS] Course Completed');

        $body = (string)($p['body'] ?? '');
        $lines = preg_split("/\r\n|\n|\r/", $body);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { $m->line(' '); continue; }
            $m->line($line);
        }

        if (!empty($p['certificate_url'])) {
            $m->action('Download Certificate', $p['certificate_url']);
        } elseif (!empty($p['portal_url'])) {
            $m->action('Open LMS', $p['portal_url']);
        }

        return $m;
    }
}
