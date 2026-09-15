<?php

namespace App\Listeners;

use App\Events\TicketEscalated;
use App\Jobs\SendEscalationNotificationJob;
use App\Models\EscalationNotification;

class DispatchEscalationNotifications
{
    public function handle(TicketEscalated $event): void
    {
        foreach ($event->channels as $channel) {
            $log = EscalationNotification::create([
                'ticket_id' => $event->ticket->id,
                'channel' => $channel,
            ]);

            SendEscalationNotificationJob::dispatch($log->id);
        }
    }
}
