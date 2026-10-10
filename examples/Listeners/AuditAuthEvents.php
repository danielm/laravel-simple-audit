<?php

// Example only — copy into app/Listeners/AuditAuthEvents.php, then
// register it in app/Providers/EventServiceProvider.php:
//
//   protected $subscribe = [
//       \App\Listeners\AuditAuthEvents::class,
//   ];
//
// Laravel already fires these events from its built-in auth scaffolding
// (Breeze, Jetstream, Fortify, or Auth::login()/logout() directly), so
// this subscriber needs no custom events of your own — it just turns the
// ones Laravel already dispatches into audit rows.

namespace App\Listeners;

use App\Enums\AuditEventType;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

class AuditAuthEvents
{
    public function onRegistered(Registered $event): void
    {
        audit()
            ->by($event->user)
            ->performedOn($event->user)
            ->log(AuditEventType::UserRegistered);
    }

    public function onVerified(Verified $event): void
    {
        audit()
            ->by($event->user)
            ->performedOn($event->user)
            ->log(AuditEventType::UserVerified);
    }

    public function onLogin(Login $event): void
    {
        audit()
            ->by($event->user)
            ->performedOn($event->user)
            ->withProperties(['guard' => $event->guard])
            ->log(AuditEventType::UserLoggedIn);
    }

    public function onLogout(Logout $event): void
    {
        // $event->user can be null if the session had already expired.
        audit()
            ->by($event->user)
            ->performedOn($event->user)
            ->log(AuditEventType::UserLoggedOut);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        // Security-sensitive: written synchronously so it's never lost
        // even if the queue is temporarily unavailable.
        audit()
            ->by($event->user)
            ->performedOn($event->user)
            ->synchronously()
            ->log(AuditEventType::UserPasswordReset);
    }

    public function onLockout(Lockout $event): void
    {
        // No authenticated user to attach here — the login attempt failed —
        // so this logs with properties only, no actor/subject.
        audit()
            ->withProperties([
                'ip' => $event->request->ip(),
                'email' => $event->request->input('email'),
            ])
            ->log(AuditEventType::UserLockedOut);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'onRegistered',
            Verified::class => 'onVerified',
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            PasswordReset::class => 'onPasswordReset',
            Lockout::class => 'onLockout',
        ];
    }
}
