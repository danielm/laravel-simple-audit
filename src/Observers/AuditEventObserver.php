<?php

namespace Danielm\LaravelSimpleAudit\Observers;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use LogicException;

/**
 * Audit rows are append-only: once written, they must never be changed or
 * removed through Eloquent. Wired onto the model via #[ObservedBy] rather
 * than registered in a service provider.
 *
 * The simple-audit:archive command bypasses this deliberately by deleting
 * through the query builder directly, since that's the one sanctioned way
 * old rows are ever removed (see ArchiveAuditEvents).
 */
class AuditEventObserver
{
    public function updating(AuditEvent $auditEvent): void
    {
        throw new LogicException('Audit events are immutable and cannot be updated.');
    }

    public function deleting(AuditEvent $auditEvent): void
    {
        throw new LogicException('Audit events are immutable and cannot be deleted. Use archiving instead.');
    }
}
