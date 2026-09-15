<?php

namespace Tests\Fakes;

use App\Exceptions\NotificationSendException;
use App\Models\Ticket;
use App\Services\Notifications\Channels\NotificationChannel;

/**
 * Test double that fails a configurable number of times before succeeding,
 * or fails forever when $failTimes is null. Used to exercise the job's
 * retry/backoff behaviour without depending on real Email/Slack services.
 */
class FakeFlakyChannel implements NotificationChannel
{
    public int $calls = 0;

    public function __construct(private readonly ?int $failTimes = null)
    {
        //
    }

    public function send(Ticket $ticket): void
    {
        $this->calls++;

        if ($this->failTimes === null || $this->calls <= $this->failTimes) {
            throw new NotificationSendException("Simulated failure #{$this->calls}");
        }
    }
}
