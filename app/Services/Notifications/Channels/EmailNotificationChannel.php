<?php

namespace App\Services\Notifications\Channels;

use App\Exceptions\NotificationSendException;
use App\Mail\TicketEscalatedMail;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailNotificationChannel implements NotificationChannel
{
    public function send(Ticket $ticket): void
    {
        $recipient = $ticket->agent?->email ?? config('services.escalation.fallback_email');

        if (! $recipient) {
            throw new NotificationSendException(
                'Email channel is not configured: ticket has no assigned agent and no fallback escalation email is set.'
            );
        }

        try {
            Mail::to($recipient)->send(new TicketEscalatedMail($ticket));
        } catch (Throwable $e) {
            throw new NotificationSendException("Email service unavailable: {$e->getMessage()}", previous: $e);
        }
    }
}
