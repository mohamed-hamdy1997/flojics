<?php

namespace App\Services\Notifications\Channels;

use App\Exceptions\NotificationSendException;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Throwable;

class SlackNotificationChannel implements NotificationChannel
{
    public function send(Ticket $ticket): void
    {
        $token = config('services.slack.notifications.bot_user_oauth_token');
        $channel = config('services.slack.notifications.channel');

        if (! $token || ! $channel) {
            throw new NotificationSendException(
                'Slack channel is not configured: missing bot token or default channel.'
            );
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->post('https://slack.com/api/chat.postMessage', [
                    'channel' => $channel,
                    'text' => sprintf(
                        ':rotating_light: Ticket #%d "%s" has been escalated (priority: %s).',
                        $ticket->id,
                        $ticket->subject,
                        $ticket->priority->value,
                    ),
                ]);
        } catch (Throwable $e) {
            throw new NotificationSendException("Slack request timeout/error: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            throw new NotificationSendException(
                'Slack webhook failure: '.($response->json('error') ?? "HTTP {$response->status()}")
            );
        }
    }
}
