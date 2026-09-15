<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $agents = Agent::factory()->count(3)->create();
        $customers = Customer::factory()->count(5)->create();

        Ticket::factory()->create([
            'customer_id' => $customers->random()->id,
            'agent_id' => $agents->random()->id,
            'subject' => 'Cannot log in to my account',
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Open,
        ]);

        Ticket::factory()->create([
            'customer_id' => $customers->random()->id,
            'agent_id' => $agents->random()->id,
            'subject' => 'Billing charged twice this month',
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::InProgress,
        ]);

        Ticket::factory()->create([
            'customer_id' => $customers->random()->id,
            'agent_id' => null,
            'subject' => 'Feature request: dark mode',
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Open,
        ]);

        Ticket::factory()->escalated()->create([
            'customer_id' => $customers->random()->id,
            'agent_id' => $agents->random()->id,
            'subject' => 'Production API returning 500 errors',
            'priority' => TicketPriority::Urgent,
        ]);

        Ticket::factory()->create([
            'customer_id' => $customers->random()->id,
            'agent_id' => $agents->random()->id,
            'subject' => 'Password reset email not arriving',
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::Resolved,
        ]);
    }
}
