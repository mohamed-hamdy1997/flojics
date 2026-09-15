<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'agent_id' => Agent::factory(),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'status' => TicketStatus::Open,
            'escalated_at' => null,
        ];
    }

    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Escalated,
            'escalated_at' => now(),
        ]);
    }
}
