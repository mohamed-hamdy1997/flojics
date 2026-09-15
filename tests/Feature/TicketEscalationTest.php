<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TicketEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ticket_can_be_escalated_successfully(): void
    {
        Event::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $response = $this->postJson("/api/tickets/{$ticket->id}/escalate");

        $response->assertOk();
        $response->assertJsonPath('ticket.status', 'Escalated');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::Escalated->value,
        ]);

        $ticket->refresh();
        $this->assertNotNull($ticket->escalated_at);

        Event::assertDispatched(TicketEscalated::class, function (TicketEscalated $event) use ($ticket) {
            return $event->ticket->id === $ticket->id
                && $event->channels === ['email', 'slack'];
        });
    }

    public function test_escalating_a_specific_subset_of_channels(): void
    {
        Event::fake();

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->postJson("/api/tickets/{$ticket->id}/escalate", [
            'channels' => ['email'],
        ])->assertOk();

        Event::assertDispatched(TicketEscalated::class, fn (TicketEscalated $event) => $event->channels === ['email']);
    }

    public function test_escalating_an_invalid_ticket_returns_404(): void
    {
        $this->postJson('/api/tickets/999999/escalate')->assertNotFound();
    }

    public function test_escalating_an_already_escalated_ticket_returns_422(): void
    {
        $ticket = Ticket::factory()->escalated()->create();

        $response = $this->postJson("/api/tickets/{$ticket->id}/escalate");

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Ticket is already escalated.']);
    }

    public function test_escalating_with_an_unsupported_channel_returns_422(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $response = $this->postJson("/api/tickets/{$ticket->id}/escalate", [
            'channels' => ['carrier_pigeon'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['channels.0']);
    }
}
