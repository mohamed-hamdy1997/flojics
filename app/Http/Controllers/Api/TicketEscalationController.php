<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Http\Controllers\Controller;
use App\Http\Requests\EscalateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TicketEscalationController extends Controller
{
    public function __invoke(EscalateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->isEscalated()) {
            return response()->json([
                'message' => 'Ticket is already escalated.',
            ], 422);
        }

        $channels = $request->channels();

        DB::transaction(function () use ($ticket) {
            $ticket->update([
                'status' => TicketStatus::Escalated,
                'escalated_at' => now(),
            ]);
        });

        event(new TicketEscalated($ticket->fresh(['customer', 'agent']), $channels));

        return response()->json([
            'message' => 'Ticket escalated. Notifications are being dispatched.',
            'ticket' => new TicketResource($ticket->load(['customer', 'agent'])),
        ]);
    }
}
