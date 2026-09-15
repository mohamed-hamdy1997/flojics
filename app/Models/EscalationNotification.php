<?php

namespace App\Models;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationStatus;
use Database\Factories\EscalationNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'channel', 'status', 'attempts', 'last_error', 'sent_at'])]
class EscalationNotification extends Model
{
    /** @use HasFactory<EscalationNotificationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannelType::class,
            'status' => NotificationStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
