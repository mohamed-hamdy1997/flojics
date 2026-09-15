<?php

use App\Services\Notifications\Channels\EmailNotificationChannel;
use App\Services\Notifications\Channels\SlackNotificationChannel;

return [

    /*
    |--------------------------------------------------------------------------
    | Escalation Notification Channels
    |--------------------------------------------------------------------------
    |
    | Maps a channel key (used in the API request and stored on
    | escalation_notifications.channel) to the Strategy class that knows
    | how to deliver it. To support a future channel (WhatsApp, SMS,
    | Microsoft Teams, Push...):
    |
    |   1. Create App\Services\Notifications\Channels\<Name>NotificationChannel
    |      implementing App\Services\Notifications\Channels\NotificationChannel.
    |   2. Add it to the map below.
    |
    | No other application code needs to change.
    |
    */

    'channels' => [
        'email' => EmailNotificationChannel::class,
        'slack' => SlackNotificationChannel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Channels
    |--------------------------------------------------------------------------
    |
    | Used when the escalate request does not specify which channels to
    | notify through.
    |
    */

    'default' => ['email', 'slack'],

];
