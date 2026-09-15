# Database Design

## Tables Created

### `customers`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string | unique |
| phone | string | nullable |
| timestamps | | |

### `agents`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK -> users.id | nullable, unique, `nullOnDelete` — optional link to a login-capable `User` |
| name | string | |
| email | string | unique — used as the Email channel recipient |
| timestamps | | |

### `tickets`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| customer_id | bigint FK -> customers.id | `cascadeOnDelete` |
| agent_id | bigint FK -> agents.id | nullable, `nullOnDelete` |
| subject | string | |
| description | text | nullable |
| priority | string | Low / Medium / High / Urgent (`TicketPriority` enum cast) |
| status | string | Open / In Progress / **Escalated** / Resolved / Closed (`TicketStatus` enum cast), default `Open` |
| escalated_at | timestamp | nullable — set when status becomes Escalated |
| timestamps | | |

Indexes: `status`, `escalated_at` (both queried when filtering/reporting on escalations).

### `escalation_notifications`
One row per (ticket, channel) escalation attempt — the audit/retry log.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| ticket_id | bigint FK -> tickets.id | `cascadeOnDelete` |
| channel | string | e.g. `email`, `slack` (`NotificationChannelType` enum cast) |
| status | string | pending / sent / failed (`NotificationStatus` enum cast), default `pending` |
| attempts | unsigned tinyint | default 0, incremented on every delivery attempt |
| last_error | text | nullable — most recent failure message |
| sent_at | timestamp | nullable — set only on success |
| timestamps | | |

Index: composite `(ticket_id, channel)` for the common "notifications for this ticket" lookup.

## Relationships

```
Customer  1 ── * Ticket
Agent     1 ── * Ticket            (nullable — a ticket may be unassigned)
User      1 ── 1 Agent             (nullable — an agent may not have a login account)
Ticket    1 ── * EscalationNotification
```

## Constraints

- `customers.email` and `agents.email` are unique.
- `agents.user_id` is unique (one login user maps to at most one agent) and nullable.
- All foreign keys are enforced at the database level; `tickets.customer_id` cascades deletes,
  `tickets.agent_id` and `agents.user_id` set null on delete so removing an agent/user doesn't
  destroy ticket history.
- `escalation_notifications.ticket_id` cascades deletes so removing a ticket cleans up its
  notification log.
