<?php

namespace Database\Factories;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationStatus;
use App\Models\EscalationNotification;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EscalationNotification>
 */
class EscalationNotificationFactory extends Factory
{
    protected $model = EscalationNotification::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'channel' => NotificationChannelType::Email,
            'status' => NotificationStatus::Pending,
            'attempts' => 0,
            'last_error' => null,
            'sent_at' => null,
        ];
    }
}
