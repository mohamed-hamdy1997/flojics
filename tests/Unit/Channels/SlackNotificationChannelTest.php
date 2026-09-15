<?php

namespace Tests\Unit\Channels;

use App\Exceptions\NotificationSendException;
use App\Models\Ticket;
use App\Services\Notifications\Channels\SlackNotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack.notifications.bot_user_oauth_token' => 'xoxb-fake-token',
            'services.slack.notifications.channel' => '#escalations',
        ]);
    }

    public function test_it_posts_to_slack_successfully(): void
    {
        Http::fake([
            'slack.com/*' => Http::response(['ok' => true], 200),
        ]);

        $ticket = Ticket::factory()->create();

        (new SlackNotificationChannel)->send($ticket);

        Http::assertSent(fn ($request) => $request->url() === 'https://slack.com/api/chat.postMessage'
            && $request['channel'] === '#escalations'
        );
    }

    public function test_it_throws_on_slack_webhook_failure(): void
    {
        Http::fake([
            'slack.com/*' => Http::response(['ok' => false, 'error' => 'channel_not_found'], 200),
        ]);

        $ticket = Ticket::factory()->create();

        $this->expectException(NotificationSendException::class);
        $this->expectExceptionMessage('channel_not_found');

        (new SlackNotificationChannel)->send($ticket);
    }

    public function test_it_throws_on_timeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $ticket = Ticket::factory()->create();

        $this->expectException(NotificationSendException::class);

        (new SlackNotificationChannel)->send($ticket);
    }

    public function test_it_throws_when_not_configured(): void
    {
        config(['services.slack.notifications.bot_user_oauth_token' => null]);

        $ticket = Ticket::factory()->create();

        $this->expectException(NotificationSendException::class);

        (new SlackNotificationChannel)->send($ticket);
    }
}
