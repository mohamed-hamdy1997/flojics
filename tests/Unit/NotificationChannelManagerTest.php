<?php

namespace Tests\Unit;

use App\Services\Notifications\Channels\EmailNotificationChannel;
use App\Services\Notifications\Channels\SlackNotificationChannel;
use App\Services\Notifications\NotificationChannelManager;
use InvalidArgumentException;
use Tests\TestCase;

class NotificationChannelManagerTest extends TestCase
{
    public function test_it_resolves_the_email_channel(): void
    {
        $manager = $this->app->make(NotificationChannelManager::class);

        $this->assertInstanceOf(EmailNotificationChannel::class, $manager->resolve('email'));
    }

    public function test_it_resolves_the_slack_channel(): void
    {
        $manager = $this->app->make(NotificationChannelManager::class);

        $this->assertInstanceOf(SlackNotificationChannel::class, $manager->resolve('slack'));
    }

    public function test_it_throws_for_an_unknown_channel(): void
    {
        $manager = $this->app->make(NotificationChannelManager::class);

        $this->expectException(InvalidArgumentException::class);

        $manager->resolve('carrier_pigeon');
    }

    public function test_enabled_keys_reflects_the_config_map(): void
    {
        $manager = $this->app->make(NotificationChannelManager::class);

        $this->assertSame(['email', 'slack'], $manager->enabledKeys());
    }
}
