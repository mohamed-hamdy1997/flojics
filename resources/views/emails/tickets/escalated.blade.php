<x-mail::message>
# Ticket Escalated

Ticket **#{{ $ticket->id }}** has been escalated and needs attention.

- **Subject:** {{ $ticket->subject }}
- **Priority:** {{ $ticket->priority->value }}
- **Status:** {{ $ticket->status->value }}
- **Escalated at:** {{ $ticket->escalated_at?->format('Y-m-d H:i') }}

<x-mail::button :url="url('/tickets')">
View Tickets
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
