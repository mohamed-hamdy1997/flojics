<?php

namespace Tests\Unit\Channels;

use App\Exceptions\NotificationSendException;
use App\Mail\TicketEscalatedMail;
use App\Models\Agent;
use App\Models\Ticket;
use App\Services\Notifications\Channels\EmailNotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_emails_the_assigned_agent(): void
    {
        Mail::fake();

        $agent = Agent::factory()->create(['email' => 'agent@example.com']);
        $ticket = Ticket::factory()->create(['agent_id' => $agent->id]);

        (new EmailNotificationChannel)->send($ticket);

        Mail::assertSent(TicketEscalatedMail::class, fn (TicketEscalatedMail $mail) => $mail->hasTo('agent@example.com')
            && $mail->ticket->id === $ticket->id
        );
    }

    public function test_it_falls_back_to_the_configured_escalation_email_when_unassigned(): void
    {
        Mail::fake();
        config(['services.escalation.fallback_email' => 'fallback@example.com']);

        $ticket = Ticket::factory()->create(['agent_id' => null]);

        (new EmailNotificationChannel)->send($ticket);

        Mail::assertSent(TicketEscalatedMail::class, fn (TicketEscalatedMail $mail) => $mail->hasTo('fallback@example.com'));
    }

    public function test_it_throws_when_no_recipient_can_be_determined(): void
    {
        config(['services.escalation.fallback_email' => null]);

        $ticket = Ticket::factory()->create(['agent_id' => null]);

        $this->expectException(NotificationSendException::class);

        (new EmailNotificationChannel)->send($ticket);
    }
}
