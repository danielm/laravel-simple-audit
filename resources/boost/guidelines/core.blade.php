## Laravel Simple Audit

This package provides scalable, queueable audit-event logging: an
append-only `audit_events` table, an immutable `AuditEvent` model, and a
fluent builder for recording events.

### Core conventions

- Prefer the fluent builder over constructing payloads by hand: start with
  the global `audit()` helper (equivalent to `Danielm\LaravelSimpleAudit\Services\Audit::start()`).
- Event types are plain strings or `BackedEnum` cases — this package has no
  fixed list of event types. If the application already has an
  `AuditEventType` enum (or similar), use its cases; otherwise suggest a
  short, dot-namespaced string like `user.logged_in` or `invoice.refunded`.
- Never write secrets, tokens, passwords, or other sensitive values into
  `withProperties()` / `metadata` — audit rows are long-lived and not meant
  to be purged like other data.
- Audit rows are immutable. Never attempt to update or delete an
  `AuditEvent` through Eloquent — both throw `LogicException` by design.
  The only sanctioned removal path is the `simple-audit:archive` Artisan
  command, which deletes through the query builder directly after
  exporting old rows to storage.
- Use `->synchronously()` only for events that must not be lost if the
  queue is temporarily down (e.g. security-sensitive actions like a
  password reset or secret rotation). Otherwise, let events queue — this is
  the default and keeps audit writes off the request/response path.

### Usage

@verbatim
<code-snippet name="Recording an event with the fluent builder" lang="php">
audit()
    ->by($actor)                   // who did it — omit for system/console events
    ->performedOn($targetModel)    // what it happened to
    ->on($relatedModel)            // a related record for context, if any
    ->withProperties(['ip' => $request->ip()])
    ->log('user.logged_in');
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Recording an event synchronously, bypassing the queue" lang="php">
audit()->by($actor)->performedOn($targetModel)->synchronously()
    ->log('secrets.rotated');
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Querying recorded events" lang="php">
use Danielm\LaravelSimpleAudit\Models\AuditEvent;

AuditEvent::ofType('user.logged_in')
    ->forActor($user)
    ->recent()
    ->paginate();
</code-snippet>
@endverbatim

Prefer wiring audit calls into event listeners (your app's own domain
events, or Laravel's built-in ones like `Illuminate\Auth\Events\Login`)
over scattering `audit()` calls through controllers, so audit-logging
stays out of business logic. See the package's `examples/` directory for
a working subscriber built on Laravel's built-in auth events.
