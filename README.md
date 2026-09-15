# Flojics Technical Assessment — Ticket Escalation Notifications

Laravel 13 + Vue 3 + Inertia.js implementation of the ticket escalation feature described in the
assessment brief: `POST /api/tickets/{id}/escalate` sets a ticket's status to `Escalated`,
records the escalation date, and delivers notifications through pluggable channels (Email,
Slack — Strategy pattern, easily extendable) with automatic retries.

See `docs/` for the requirement analysis, architecture notes, database design, and test cases.

## Requirements

- PHP >= 8.3 (developed against 8.4)
- Composer 2
- Node.js 20+ / npm
- MySQL (a running server reachable from `.env`)

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). To
exercise Slack delivery, also set `SLACK_BOT_USER_OAUTH_TOKEN` and
`SLACK_BOT_USER_DEFAULT_CHANNEL` (a real Slack bot token with `chat:write` scope); without it,
the Slack channel will fail and retry as designed — which is a fine way to see the retry
mechanism in action. `ESCALATION_FALLBACK_EMAIL` is used for the Email channel when a ticket has
no assigned agent. `MAIL_MAILER=log` (the default) writes outgoing emails to
`storage/logs/laravel.log` instead of sending real mail.

```bash
php artisan migrate --seed
npm run build   # or `npm run dev` for local development with hot reload
php artisan serve
```

Visit `http://127.0.0.1:8000/tickets` (or wherever `artisan serve` binds) — `/` redirects there.

### Processing notification retries

Notifications are delivered via Laravel's queue (`QUEUE_CONNECTION=database` by default, using
the built-in `jobs` table). Run a worker so escalations actually get delivered/retried:

```bash
php artisan queue:work
```

Without a worker running, escalating a ticket still updates its status immediately, but the
Email/Slack jobs will sit queued (and their retries delayed) until a worker processes them.

## Running Tests

```bash
php artisan test
```

Tests run against a MySQL database configured in `phpunit.xml`
(`DB_DATABASE=flojics_testing` — create it once with
`CREATE DATABASE flojics_testing;`). This project uses MySQL for tests instead of SQLite because
the assessment's technical requirement is MySQL; `RefreshDatabase` handles schema/state per test.

## What's Implemented

- `POST /api/tickets/{id}/escalate` — escalates a ticket, dispatches notifications, returns the
  updated ticket. Validates the ticket exists (404), rejects re-escalating an already-escalated
  ticket (422), and validates an optional `channels` array (422 on unsupported channels).
- **Strategy + Registry pattern** for notification channels (`app/Services/Notifications`) —
  Email and Slack today, designed so a new channel (WhatsApp, SMS, Teams, Push) is a new class +
  one config line, no other code changes. See `docs/02-architecture.md`.
- **Automatic retry** (up to 3 attempts, 10s/30s backoff) via a queued job, with the final
  result and attempt count persisted per (ticket, channel) in `escalation_notifications`.
- A minimal **Vue 3 + Inertia.js** page at `/tickets` listing Ticket ID, Subject, Priority,
  Status, and Escalation Date, with an Escalate button that updates the row in place.
- 20 PHPUnit tests covering the endpoint, the retry job, and both channel strategies.

## Documentation

- [`docs/01-requirement-analysis.md`](docs/01-requirement-analysis.md) — Questions, Assumptions, Recommendations
- [`docs/02-architecture.md`](docs/02-architecture.md) — Folder structure, design decisions, notification architecture, retry strategy, extending with new channels
- [`docs/03-database-design.md`](docs/03-database-design.md) — Tables, relationships, indexes/constraints
- [`docs/04-test-cases.md`](docs/04-test-cases.md) — Test case matrix and self-testing notes
