<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(): Response
    {
        $tickets = Ticket::with(['customer', 'agent'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
        ]);
    }
}
