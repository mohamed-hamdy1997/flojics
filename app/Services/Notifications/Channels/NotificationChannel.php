<?php

namespace App\Services\Notifications\Channels;

use App\Exceptions\NotificationSendException;
use App\Models\Ticket;

/**
 * Strategy contract for a single notification delivery channel.
 *
 * To add a new channel: implement this interface and register the class
 * against a channel key in config/notification_channels.php. No other
 * code needs to change.
 */
interface NotificationChannel
{
    /**
     * Deliver the escalation notification for the given ticket.
     *
     * @throws NotificationSendException when delivery fails.
     */
    public function send(Ticket $ticket): void;
}
