# Laravel Simple Audit

Scalable, queueable audit-event logging for Laravel, with a fluent builder:

![tests](https://github.com/danielm/laravel-simple-audit/actions/workflows/tests.yml/badge.svg)

```php
audit()
    ->by($actor)
    ->performedOn($targetModel)
    ->withProperties(['ip' => $request->ip()])
    ->log('user.logged_in');
```

This package provides the generic engine — an append-only `audit_events`
table, an immutable model, a queued write path, a fluent builder, and an
archiving command. **It has no opinion on what your event types are** —
pass any string or `BackedEnum` to `->log()`. The `examples/` folder shows
one way to define your own event types and wire them up, using Laravel's
own built-in auth events as a concrete starting point.

## Requirements

- PHP 8.2+
- Laravel 12.4+ or 13 (`AuditEvent`'s scopes use the `#[Scope]` attribute,
  added in Laravel 12.4 — composer.json enforces this minimum)

## Installing it

```bash
composer require danielm/laravel-simple-audit
```

Laravel's package auto-discovery registers `LaravelSimpleAuditServiceProvider`
automatically — no manual provider registration needed.

## Setup

1. Run the migration (it's auto-loaded from the package, no publish needed):
   ```bash
   php artisan migrate
   ```
2. (Optional) Publish the config file to customize table name, connection,
   queue, or archiving defaults:
   ```bash
   php artisan vendor:publish --tag=simple-audit-config
   ```
3. (Optional) Publish the migration instead of relying on the package's
   copy, if you need to hand-edit the schema:
   ```bash
   php artisan vendor:publish --tag=simple-audit-migrations
   ```
4. Create a dedicated `audit` queue worker (recommended — keep it separate
   from your default queue so a backlog there never affects audit writes):
   ```bash
   php artisan queue:work --queue=audit
   ```
5. (Optional) Copy the examples into your app as a starting point:
   ```bash
   php artisan vendor:publish --tag=simple-audit-examples
   ```
   See `examples/README.md` for what's in there and how to wire it up.

## Usage

Log directly with the fluent builder, available globally via `audit()`:

```php
audit()
    ->by($actor)                   // who did it — omit for system/console events
    ->performedOn($targetModel)    // what it happened to
    ->on($relatedModel)            // a related record for context, if any
    ->withProperties(['ip' => $request->ip()])
    ->log('user.logged_in');
```

Or let a listener turn events you already dispatch — Laravel's own built-in
events, or your app's domain events — into audit rows automatically, so
audit-logging stays out of your business logic:

```php
// app/Listeners/AuditAuthEvents.php (see examples/)
public function onLogin(Login $event): void
{
    audit()->by($event->user)->performedOn($event->user)->log('user.logged_in');
}
```

`->log()` takes an optional second argument for free text, stored under
`metadata['description']`:

```php
audit()->by($actor)->performedOn($targetModel)
    ->log('settings.changed', 'Rotated after suspected leak');
```

Events that must not be lost if the queue is down can skip it entirely:

```php
audit()->by($actor)->performedOn($targetModel)->synchronously()
    ->log('settings.changed');
```

`Audit::start()` (from `Danielm\LaravelSimpleAudit\Services\Audit`) is equivalent to
`audit()` if you'd rather avoid the global helper. `Audit::record(...)`
is a one-line, non-builder form for simple cases.

## Querying

```php
use Danielm\LaravelSimpleAudit\Models\AuditEvent;

AuditEvent::ofType('user.logged_in')
    ->forActor($user)
    ->recent()
    ->paginate();

AuditEvent::forSubject($user)->between(now()->subWeek(), now())->get();
```

If you organized your event types as a `BackedEnum`, `ofType()` accepts
that directly too: `AuditEvent::ofType(AuditEventType::UserLoggedIn)`.

If you need app-specific scopes, relations, or casts, point `simple-audit.model`
at your own subclass:

```php
// config/simple-audit.php
'model' => \App\Models\AuditEvent::class,
```

```php
namespace App\Models;

use Danielm\LaravelSimpleAudit\Models\AuditEvent as BaseAuditEvent;

class AuditEvent extends BaseAuditEvent
{
    public function actorUser()
    {
        return $this->actor_type === \App\Models\User::class ? $this->actor : null;
    }
}
```

## Configuration reference (`config/simple-audit.php`)

| Key | Default | Purpose |
|---|---|---|
| `table` | `audit_events` | Table name |
| `connection` | `null` (default connection) | Isolate audit data on its own DB |
| `queue` | `audit` | Queue name for async writes |
| `queue_connection` | `null` (default connection) | Queue connection for async writes |
| `model` | `Danielm\LaravelSimpleAudit\Models\AuditEvent` | Swap in your own subclass |
| `archive.days` | `180` | Default age cutoff for `simple-audit:archive` |
| `archive.disk` | `s3` | Default filesystem disk for archives |

## Archiving / retention

```bash
php artisan simple-audit:archive --days=180 --disk=s3
```

Exports events older than the cutoff to newline-delimited JSON on the given
disk, then deletes them from the hot table. Schedule it:

```php
// routes/console.php
Schedule::command('simple-audit:archive')->monthly();
```

## Testing

Tests use [Orchestra Testbench](https://packages.tools/testbench) to boot a
minimal Laravel app and SQLite in-memory database.

```bash
composer install
composer test
```

`tests/TestCase.php` loads the package's own migration plus two tiny fixture
models (`users`, `projects`) standing in for the actor/subject/reference
models a real app would have.

## Notes

- Rows are immutable: updates/deletes throw `LogicException` (enforced by
  `AuditEventObserver`, wired onto the model via `#[ObservedBy]`), except
  via `simple-audit:archive`, which deletes through the query builder
  directly, bypassing Eloquent entirely, for retention purposes.
- Never put secrets, tokens, or raw passwords into `metadata`/properties.
- At very high volume (100M+ rows), consider partitioning `occurred_at` by
  month on MySQL/Postgres, or shipping events to ClickHouse/OpenSearch and
  keeping only recent data in SQL — this package doesn't do that for you.
