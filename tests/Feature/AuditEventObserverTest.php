<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Danielm\LaravelSimpleAudit\Observers\AuditEventObserver;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

class AuditEventObserverTest extends TestCase
{
    #[Test]
    public function updating_throws_directly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit events are immutable and cannot be updated.');

        (new AuditEventObserver)->updating(new AuditEvent);
    }

    #[Test]
    public function deleting_throws_directly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit events are immutable and cannot be deleted. Use archiving instead.');

        (new AuditEventObserver)->deleting(new AuditEvent);
    }

    #[Test]
    public function it_is_wired_onto_the_model_via_the_observed_by_attribute(): void
    {
        // Exercises the full attribute-based wiring end-to-end, rather than
        // just the observer class in isolation.
        $event = audit()->synchronously()->log('test.event');

        $this->expectException(LogicException::class);

        $event->update(['type' => 'changed']);
    }
}
