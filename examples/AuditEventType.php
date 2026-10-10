<?php

// Example only — copy into app/Enums/AuditEventType.php and adjust to your
// own events. This package has no opinion on what event types exist:
// ->log() accepts any plain string or BackedEnum, this is just one way
// to organize them.

namespace App\Enums;

enum AuditEventType: string
{
    case UserRegistered = 'user.registered';
    case UserVerified = 'user.verified';
    case UserLoggedIn = 'user.logged_in';
    case UserLoggedOut = 'user.logged_out';
    case UserPasswordReset = 'user.password_reset';
    case UserLockedOut = 'user.locked_out';
}
