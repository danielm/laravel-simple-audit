<?php

use Danielm\LaravelSimpleAudit\Services\AuditBuilder;

if (! function_exists('audit')) {
    /**
     * Start a fluent audit-log entry:
     *
     *   audit()
     *       ->by($actor)
     *       ->performedOn($targetModel)
     *       ->withProperties(['ip' => $request->ip()])
     *       ->log('user.logged_in'); // any string, or your own BackedEnum
     */
    function audit(): AuditBuilder
    {
        return new AuditBuilder;
    }
}
