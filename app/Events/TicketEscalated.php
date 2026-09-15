<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketEscalated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly array $channels,
    ) {
        //
    }
}
