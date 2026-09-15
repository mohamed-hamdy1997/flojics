# Architecture Notes

## Folder Structure (new/relevant paths)

```
app/
  Enums/
    TicketStatus.php            Open | In Progress | Escalated | Resolved | Closed
    TicketPriority.php          Low | Medium | High | Urgent
    NotificationChannelType.php email | slack   (validation / casting)
    NotificationStatus.php      pending | sent | failed
  Events/
    TicketEscalated.php         fired once a ticket's status transitions to Escalated
  Listeners/
    DispatchEscalationNotifications.php   creates a log row + queues a job per channel
  Jobs/
    SendEscalationNotificationJob.php     retryable, delivers one channel for one ticket
  Services/Notifications/
    NotificationChannelManager.php        registry/factory: channel key -> Strategy instance
    Channels/
      NotificationChannel.php             Strategy contract
      EmailNotificationChannel.php        concrete strategy
      SlackNotificationChannel.php        concrete strategy
  Mail/TicketEscalatedMail.php
  Exceptions/NotificationSendException.php
  Http/
    Controllers/Api/TicketEscalationController.php   POST /api/tickets/{id}/escalate
    Controllers/TicketController.php                  GET /tickets (Inertia page)
    Requests/EscalateTicketRequest.php
    Resources/TicketResource.php
  Models/{Customer,Agent,Ticket,EscalationNotification}.php
config/notification_channels.php   channel key -> Strategy class map + defaults
resources/js/Pages/Tickets/Index.vue   Vue 3 + Inertia page with the Escalate button
database/migrations/*_create_{customers,agents,tickets,escalation_notifications}_table.php
```

## Design Decisions

- **Strategy pattern for channels.** `NotificationChannel` is a one-method contract
  (`send(Ticket $ticket): void`, throws `NotificationSendException`). `EmailNotificationChannel`
  and `SlackNotificationChannel` are interchangeable implementations selected at runtime by key.
- **Registry/Factory (`NotificationChannelManager`) instead of a `match`/`switch`.** The map of
  channel key → class lives in `config/notification_channels.php`, resolved through the
  container (`app()->make()`), so channels can have their own constructor dependencies without
  the manager knowing about them.
- **Event/Listener to decouple the controller from delivery.** The controller only knows "a
  ticket got escalated, on these channels" (`TicketEscalated` event). `DispatchEscalationNotifications`
  turns that into persisted `escalation_notifications` rows + one queued job per channel. This
  keeps the HTTP request fast (the controller returns as soon as the ticket row is updated and
  the event is fired) and keeps notification fan-out logic in one place.
- **One job per channel, not one job that loops over channels.** A failing Slack delivery
  retries independently of a succeeding Email delivery, and each channel gets its own
  attempt/backoff timeline.
- **Native Laravel queue retry instead of a hand-rolled retry loop.** `SendEscalationNotificationJob`
  sets `$tries = 3` and a `backoff()` of `[10, 30]` seconds. Laravel's queue worker re-runs
  `handle()` automatically on failure and calls `failed()` once retries are exhausted — this is
  simpler and more battle-tested than manually looping and sleeping inside the job.

## Notification Architecture

```
Controller (validates + updates ticket)
      │  fires
      ▼
TicketEscalated event  { ticket, channels: ['email', 'slack'] }
      │  handled by
      ▼
DispatchEscalationNotifications listener
      │  for each channel key:
      │    1. creates an EscalationNotification row (status=pending, attempts=0)
      │    2. dispatches SendEscalationNotificationJob(logId)
      ▼
SendEscalationNotificationJob::handle()
      │  1. increments attempts
      │  2. NotificationChannelManager::resolve($log->channel) -> Strategy
      │  3. Strategy::send($ticket)
      │       success -> log: status=sent, sent_at=now()
      │       failure -> log: last_error=..., rethrow (queue retries)
      ▼
Job::failed() (only once retries are exhausted) -> log: status=failed
```

## Retry Strategy

- `$tries = 3` on the job = **3 total delivery attempts** per channel.
- `backoff() => [10, 30]` = 10s before the 2nd attempt, 30s before the 3rd.
- `attempts` on `escalation_notifications` is incremented at the start of every `handle()` call,
  so it always reflects how many times delivery was actually tried, independent of whether the
  job ultimately succeeded or failed.
- `status` transitions `pending -> sent` (success) or `pending -> failed` (all 3 attempts
  exhausted); `last_error` always holds the most recent failure message, `sent_at` is set only
  on success.
- Running this for real requires a queue worker (`php artisan queue:work`) since retries/backoff
  are a property of Laravel's queue worker loop, not the `sync` driver.

## Adding a New Channel (e.g. WhatsApp)

1. Create `App\Services\Notifications\Channels\WhatsAppNotificationChannel` implementing
   `NotificationChannel::send(Ticket $ticket): void`.
2. Add it to `config/notification_channels.php`: `'whatsapp' => WhatsAppNotificationChannel::class`.
3. (Optional) add `'whatsapp'` to the `default` array if it should fire automatically.

No changes are needed to the controller, event, listener, job, or database schema — the
`channels` column and the `NotificationChannel` validation rule already accept any key present
in the config map.
