# Test Cases

Automated as PHPUnit tests (`php artisan test`, 20 tests / 41 assertions, all passing).

## `POST /api/tickets/{id}/escalate` — `tests/Feature/TicketEscalationTest.php`

| # | Scenario | Steps | Expected Result |
|---|---|---|---|
| 1 | Successful escalation | POST to an `Open` ticket, no `channels` param | `200`, ticket `status = Escalated`, `escalated_at` set, `TicketEscalated` event dispatched with default channels `['email', 'slack']` |
| 2 | Escalate with a specific channel subset | POST with `channels: ['email']` | `200`, `TicketEscalated` event dispatched with `channels = ['email']` only |
| 3 | Invalid ticket | POST to `/api/tickets/999999/escalate` | `404 Not Found` (route-model binding) |
| 4 | Already-escalated ticket | POST to a ticket whose status is already `Escalated` | `422 Unprocessable Entity`, `"Ticket is already escalated."` |
| 5 | Unsupported channel | POST with `channels: ['carrier_pigeon']` | `422`, validation error on `channels.0` |

## Notification Retry — `tests/Feature/EscalationNotificationJobTest.php`

Uses a `FakeFlakyChannel` test double (`tests/Fakes/FakeFlakyChannel.php`) bound in place of the
real Email/Slack strategy so retry behaviour can be exercised deterministically and instantly
(no network calls, no waiting for real queue backoff).

| # | Scenario | Steps | Expected Result |
|---|---|---|---|
| 6 | Retry success | Channel fails on attempts 1 & 2, succeeds on attempt 3 — job's `handle()` invoked 3 times | After attempt 1: `attempts=1`, `status=pending`, `last_error` set. After attempt 2: `attempts=2`, still `pending`. After attempt 3: `attempts=3`, `status=sent`, `sent_at` set, `last_error` cleared |
| 7 | Retry exhausted | Channel always fails — `handle()` invoked `$tries` (3) times, then `failed()` is invoked (simulating the queue giving up) | `attempts=3`, `status` stays `pending` until `failed()` runs, then becomes `failed` with `last_error` set to the last failure message |

## Channel Strategies

### `tests/Unit/Channels/EmailNotificationChannelTest.php`
| # | Scenario | Expected Result |
|---|---|---|
| 8 | Ticket has an assigned agent | `Mail::fake()` — a `TicketEscalatedMail` is sent to the agent's email |
| 9 | Ticket has no agent, fallback email configured | Mail sent to `services.escalation.fallback_email` |
| 10 | Ticket has no agent and no fallback configured | `NotificationSendException` thrown |

### `tests/Unit/Channels/SlackNotificationChannelTest.php`
| # | Scenario | Expected Result |
|---|---|---|
| 11 | Slack API returns `ok: true` | No exception; request sent to `chat.postMessage` with the configured channel |
| 12 | Slack API returns `ok: false` (webhook/API failure) | `NotificationSendException` with the Slack `error` code in the message |
| 13 | Slack request times out / connection error | `NotificationSendException` |
| 14 | Bot token or channel not configured | `NotificationSendException` before any HTTP call is made |

### `tests/Unit/NotificationChannelManagerTest.php`
| # | Scenario | Expected Result |
|---|---|---|
| 15–16 | Resolve `email` / `slack` | Returns the matching concrete Strategy instance |
| 17 | Resolve an unknown key | `InvalidArgumentException` |
| 18 | `enabledKeys()` | Returns `['email', 'slack']`, matching `config/notification_channels.php` |

## Self-Testing Notes

- **Automated**: all scenarios above run via `php artisan test` against a real MySQL database
  (`flojics_testing`, `RefreshDatabase` trait) — chosen over SQLite because the PHP 8.4 CLI in
  this environment doesn't have `pdo_sqlite` installed, and the assessment's technical
  requirement is MySQL anyway.
- **Manual, end-to-end**: ran `php artisan serve`, escalated a real seeded ticket through the API
  with `curl`, confirmed via `mysql` that `tickets.status`/`escalated_at` and two
  `escalation_notifications` rows (email, slack) were created, then ran
  `php artisan queue:work --stop-when-empty` and confirmed: the Email job succeeded (rendered
  Markdown mail visible in `storage/logs/laravel.log` since `MAIL_MAILER=log`), and the Slack job
  failed as expected (no bot token configured in this local environment) and was automatically
  re-queued with a delayed `available_at` for its backoff retry, with `attempts` and `last_error`
  correctly recorded on the `escalation_notifications` row.
- **Frontend**: verified the raw Inertia JSON payload for `GET /tickets` (via `curl` with the
  `X-Inertia` header) matches what `resources/js/Pages/Tickets/Index.vue` expects
  (`tickets.data` array of `TicketResource`-shaped objects), and built the production assets
  with `npm run build` without errors.
