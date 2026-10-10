# Examples

These files are **starting points to copy into your app**, not part of the
installable package — nothing here is autoloaded by Composer. Publish them
with:

```bash
php artisan vendor:publish --tag=simple-audit-examples
```

which copies this folder to `simple-audit-examples/` at your project root.
From there, move what you want into your app and delete the rest.

## What's here

- **`AuditEventType.php`** — an example enum of event types, limited to
  common user/auth events. Move it to `app/Enums/AuditEventType.php` and
  add your own cases as needed. `->log()` accepts any string or
  `BackedEnum`, so this is one organizational pattern, not a requirement.
- **`Listeners/AuditAuthEvents.php`** — a subscriber that hooks into
  Laravel's **built-in** auth events (`Registered`, `Verified`, `Login`,
  `Logout`, `PasswordReset`, `Lockout`). These already fire automatically
  if you use Laravel's auth scaffolding (Breeze, Jetstream, Fortify) or
  call `Auth::login()` / `Auth::logout()` yourself — no custom events
  needed for this case.

## Two ways to record events

**1. Event-driven** (what the example above does): let a subscriber turn
events you already dispatch — Laravel's own, or your app's domain events —
into audit rows, keeping audit-logging concerns out of your business logic.

**2. Direct, inline calls**: for actions that don't already fire an event,
call the builder right where the action happens — a controller, a form
request, a job, a console command:

```php
// e.g. inside a controller action, after some domain-specific action
audit()
    ->by($request->user())
    ->performedOn($someModel)
    ->withProperties(['reason' => $request->input('reason')])
    ->log('some.action_happened');
```

Both approaches write through the same `audit()` builder and the same
`audit_events` table — pick whichever fits a given action better. Most
apps end up using a mix of both.
