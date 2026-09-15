# Requirement Analysis

## Questions for the Product Owner

These would normally be asked before starting. Per the assessment instructions,
implementation proceeded on reasonable assumptions (below) without waiting for answers.

1. **Recipients** — When a ticket is escalated, who exactly should be notified? The assigned
   agent only, a whole team/channel, the customer, or a configurable escalation policy
   (e.g. "notify the agent's manager")?
2. **Channel selection** — Is the set of channels chosen per-click on the Escalate button (as
   implemented), a per-ticket/per-customer default, or a global setting configured by an admin?
3. **Multi-tenancy / auth** — Is this a single-tenant internal tool or a multi-tenant SaaS where
   agents only see their own organization's tickets? Should the `/tickets` page and the escalate
   endpoint require authentication?
4. **Re-escalation** — Can an already-escalated ticket be escalated again (e.g. to re-notify or
   to bump priority further), or is escalation a one-time, one-way transition?
5. **SLA linkage** — Does escalation need to trigger any SLA timers, reporting, or downstream
   billing/audit events beyond the notification itself?
6. **Retry semantics** — Does "retry up to 3 times" mean 3 attempts total or 3 retries after the
   first attempt (4 attempts total)? What backoff delay is acceptable between retries?
7. **Channel failure visibility** — If a channel keeps failing (e.g. Slack token expires), who
   should be alerted, and how (dashboard, email to admins, paging)?
8. **Per-agent channel preferences** — Should individual agents be able to opt in/out of
   specific channels (e.g. no Slack DMs after hours)?

## Assumptions Made

- **Recipients**: the assigned agent's email is the Email channel recipient; if the ticket has
  no agent, a configurable fallback address (`ESCALATION_FALLBACK_EMAIL`) is used. Slack posts to
  a single configured team channel (`SLACK_BOT_USER_DEFAULT_CHANNEL`) via the Slack Web API
  (`chat.postMessage` with a bot token), not a per-agent DM — the app skeleton already had a
  `services.slack.notifications` config block for this.
- **Channel selection**: the caller (frontend) selects channels per escalation via an optional
  `channels` array in the request body; if omitted, all configured channels
  (`notification_channels.default` = `['email', 'slack']`) are used.
- **Auth**: no authentication is required for `/tickets` or the escalate endpoint — this is a
  single-tenant demo/assessment scope. In production this would sit behind Laravel auth
  middleware and tenant scoping.
- **Re-escalation**: a ticket that is already `Escalated` cannot be escalated again — the
  endpoint returns `422 Unprocessable Entity`. This avoids duplicate notification spam.
- **Retry semantics**: "up to 3 times" = **3 total attempts** (`$tries = 3` on the queued job),
  with backoff delays of 10s then 30s between attempts.
- **Failure visibility**: the final outcome and last error are persisted on
  `escalation_notifications` for inspection; no external alerting is wired up (see
  Recommendations).
- **Channel scope**: every selected channel is notified the same way for every ticket; there is
  no per-agent channel preference in this version.

## Recommendations

- **Idempotency key** on the escalate request to make retried client calls (e.g. a flaky network
  causing a double-click) safe without relying solely on the "already escalated" guard.
- **Dead-letter alerting**: when a `SendEscalationNotificationJob` exhausts all retries, dispatch
  an internal alert (e.g. to an admin Slack channel or Sentry) so a permanently-failed
  notification doesn't go unnoticed — currently it's only visible by querying
  `escalation_notifications.status = 'failed'`.
- **Per-agent/customer channel preferences** and quiet hours, once there's a real need for it.
- **Audit trail**: a lightweight `ticket_activities` log (who escalated, when, from what status)
  would help support and compliance review, beyond just the single `escalated_at` timestamp.
- **Rate limiting** on the escalate endpoint to prevent abuse/spamming of external notification
  services.
- **Structured Slack messages** (Block Kit) instead of the current plain-text message, and a
  richer Mailable template with ticket history, once branding requirements are known.
