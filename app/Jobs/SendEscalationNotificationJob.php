<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Exceptions\NotificationSendException;
use App\Models\EscalationNotification;
use App\Services\Notifications\NotificationChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendEscalationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Total attempts allowed (satisfies "retry automatically up to 3 times").
     */
    public int $tries = 3;

    public function __construct(public readonly int $escalationNotificationId)
    {
        //
    }

    /**
     * Delay in seconds before each retry.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(NotificationChannelManager $manager): void
    {
        $log = EscalationNotification::with('ticket')->findOrFail($this->escalationNotificationId);

        $log->increment('attempts');

        $channel = $manager->resolve($log->channel->value);

        try {
            $channel->send($log->ticket);
        } catch (NotificationSendException $e) {
            $log->update(['last_error' => $e->getMessage()]);
            throw $e;
        }

        $log->update([
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Called once the job has exhausted all of its retries.
     */
    public function failed(?Throwable $exception): void
    {
        $log = EscalationNotification::find($this->escalationNotificationId);

        $log?->update([
            'status' => NotificationStatus::Failed,
            'last_error' => $exception?->getMessage(),
        ]);
    }
}
