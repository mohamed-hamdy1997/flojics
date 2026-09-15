<?php

namespace Tests\Feature;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationStatus;
use App\Exceptions\NotificationSendException;
use App\Jobs\SendEscalationNotificationJob;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use App\Services\Notifications\NotificationChannelManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeFlakyChannel;
use Tests\TestCase;

class EscalationNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(): EscalationNotification
    {
        $ticket = Ticket::factory()->create();

        return EscalationNotification::factory()->create([
            'ticket_id' => $ticket->id,
            'channel' => NotificationChannelType::Email,
        ]);
    }

    public function test_job_succeeds_after_transient_failures_and_tracks_attempts(): void
    {
        $fake = new FakeFlakyChannel(failTimes: 2);
        $this->app->instance(FakeFlakyChannel::class, $fake);
        config(['notification_channels.channels.email' => FakeFlakyChannel::class]);

        $log = $this->makeLog();
        $job = new SendEscalationNotificationJob($log->id);
        $manager = $this->app->make(NotificationChannelManager::class);

        // Attempt 1: fails
        try {
            $job->handle($manager);
            $this->fail('Expected first attempt to fail.');
        } catch (NotificationSendException) {
            //
        }
        $log->refresh();
        $this->assertSame(1, $log->attempts);
        $this->assertSame(NotificationStatus::Pending, $log->status);
        $this->assertNotNull($log->last_error);

        // Attempt 2: fails
        try {
            $job->handle($manager);
            $this->fail('Expected second attempt to fail.');
        } catch (NotificationSendException) {
            //
        }
        $log->refresh();
        $this->assertSame(2, $log->attempts);
        $this->assertSame(NotificationStatus::Pending, $log->status);

        // Attempt 3: succeeds
        $job->handle($manager);
        $log->refresh();
        $this->assertSame(3, $log->attempts);
        $this->assertSame(NotificationStatus::Sent, $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->last_error);
    }

    public function test_job_marks_notification_failed_once_retries_are_exhausted(): void
    {
        $fake = new FakeFlakyChannel(failTimes: null);
        $this->app->instance(FakeFlakyChannel::class, $fake);
        config(['notification_channels.channels.email' => FakeFlakyChannel::class]);

        $log = $this->makeLog();
        $job = new SendEscalationNotificationJob($log->id);
        $manager = $this->app->make(NotificationChannelManager::class);

        $lastException = null;

        for ($i = 0; $i < $job->tries; $i++) {
            try {
                $job->handle($manager);
            } catch (NotificationSendException $e) {
                $lastException = $e;
            }
        }

        $log->refresh();
        $this->assertSame($job->tries, $log->attempts);
        $this->assertSame(NotificationStatus::Pending, $log->status, 'Status stays pending until the queue gives up.');

        // Laravel calls failed() once the queue has exhausted all retries.
        $job->failed($lastException);

        $log->refresh();
        $this->assertSame(NotificationStatus::Failed, $log->status);
        $this->assertSame('Simulated failure #3', $log->last_error);
    }
}
