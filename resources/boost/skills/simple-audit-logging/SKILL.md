---
name: simple-audit-logging
description: Record and query audit events using Laravel Simple Audit's fluent builder, immutable model, and archiving command.
---

# Simple Audit Logging

## When to use this skill

Use this skill when the task involves recording an auditable action (who
did what, to what, and when), or querying/archiving previously recorded
audit events, in an application using Laravel Simple Audit.

## Recording events

Prefer the global `audit()` helper over instantiating the builder or model
directly:

```php
audit()
    ->by($actor)                   // the actor — a Model, or omit for system events
    ->performedOn($targetModel)    // the subject the action happened to
    ->on($relatedModel)            // optional related record for context
    ->withProperties(['key' => 'value'])
    ->log('namespace.event_name'); // string or BackedEnum
```

- `->log()` takes an optional second argument, free text stored under
  `metadata['description']`.
- By default the write is queued (non-blocking). Call `->synchronously()`
  before `->log()` only for events that must not be lost if the queue is
  down.
- Never put secrets, tokens, or passwords into `withProperties()`.

Event-driven apps typically do this from a listener/subscriber rather than
inline in a controller — see this package's `examples/Listeners/AuditAuthEvents.php`
for a subscriber built on Laravel's own auth events (`Registered`,
`Verified`, `Login`, `Logout`, `PasswordReset`, `Lockout`).

## Querying events

```php
use Danielm\LaravelSimpleAudit\Models\AuditEvent;

AuditEvent::ofType('user.logged_in')->forActor($user)->recent()->paginate();
AuditEvent::forSubject($someModel)->between(now()->subWeek(), now())->get();
```

These are attribute-based scopes (`#[Scope]`), called exactly like
traditional `scopeXxx()` scopes.

## Immutability

`AuditEvent` rows cannot be updated or deleted through Eloquent — both
throw `LogicException` (enforced by `AuditEventObserver`, wired via
`#[ObservedBy]`). Never suggest `$event->update(...)` or `$event->delete()`.
The only way to remove old rows is the archiving command below.

## Archiving old events

```bash
php artisan simple-audit:archive --days=180 --disk=s3
```

Exports events older than the cutoff to newline-delimited JSON on the
given disk, then deletes them from the hot table via the query builder
(bypassing the immutability guard, since this is the sanctioned removal
path). Defaults come from `config('simple-audit.archive')`.

## Configuration

`config/simple-audit.php` controls the table name, database connection,
queue/queue connection, the model class (`simple-audit.model`, for apps that
need app-specific scopes/casts on a subclass), and archive defaults.
