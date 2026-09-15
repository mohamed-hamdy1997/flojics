<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Channels\NotificationChannel;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registry/factory that resolves a channel key (e.g. "email") to its
 * Strategy implementation. Adding a new channel only requires registering
 * it in config/notification_channels.php.
 */
class NotificationChannelManager
{
    public function __construct(private readonly Container $container)
    {
        //
    }

    public function resolve(string $key): NotificationChannel
    {
        $map = config('notification_channels.channels', []);

        if (! isset($map[$key])) {
            throw new InvalidArgumentException("Unsupported notification channel [{$key}].");
        }

        return $this->container->make($map[$key]);
    }

    /**
     * @return array<int, string>
     */
    public function enabledKeys(): array
    {
        return array_keys(config('notification_channels.channels', []));
    }
}
