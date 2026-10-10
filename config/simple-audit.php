<?php

use Danielm\LaravelSimpleAudit\Models\AuditEvent;

return [

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    */
    'table' => env('SIMPLE_AUDIT_TABLE', 'audit_events'),

    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    | Leave null to use the app's default connection. Set this (and add a
    | matching connection in config/database.php) to isolate audit data
    | on its own database as write volume grows.
    */
    'connection' => env('SIMPLE_AUDIT_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    | Audit writes are dispatched to this queue (and, optionally, this
    | queue connection) by default so they never add latency to the
    | request/response cycle. Use ->synchronously() on the builder, or
    | pass synchronous: true to Audit::record(), to bypass the queue
    | entirely for events that must not be lost.
    */
    'queue' => env('SIMPLE_AUDIT_QUEUE', 'audit'),

    'queue_connection' => env('SIMPLE_AUDIT_QUEUE_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | Swap this if you need to extend Danielm\LaravelSimpleAudit\Models\AuditEvent
    | with app-specific behaviour (extra scopes, casts, relations).
    */
    'model' => AuditEvent::class,

    /*
    |--------------------------------------------------------------------------
    | Archiving
    |--------------------------------------------------------------------------
    | Defaults used by the `simple-audit:archive` command when its own
    | options aren't passed explicitly.
    */
    'archive' => [
        'days' => env('SIMPLE_AUDIT_ARCHIVE_DAYS', 180),
        'disk' => env('SIMPLE_AUDIT_ARCHIVE_DISK', 's3'),
    ],

];
