<?php

namespace App\Enums;

enum NotificationChannelType: string
{
    case Email = 'email';
    case Slack = 'slack';
}
