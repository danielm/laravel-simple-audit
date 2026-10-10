<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Jobs\RecordAuditEvent;
use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Danielm\LaravelSimpleAudit\Services\Audit;
use Danielm\LaravelSimpleAudit\Services\AuditBuilder;
use Danielm\LaravelSimpleAudit\Tests\Fixtures\User;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class AuditServiceTest extends TestCase
{
    #[Test]
    public function start_returns_a_builder(): void
    {
        $this->assertInstanceOf(AuditBuilder::class, Audit::start());
    }

    #[Test]
    public function record_persists_an_event_synchronously(): void
    {
        $actor = User::create(['name' => 'Alice']);

        $event = Audit::record(
            type: 'user.registered',
            actor: $actor,
            subject: $actor,
            metadata: ['source' => 'web'],
            synchronous: true,
        );

        $this->assertInstanceOf(AuditEvent::class, $event);

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'type' => 'user.registered',
            'actor_id' => $actor->id,
            'subject_id' => $actor->id,
        ]);

        $this->assertSame(['source' => 'web'], $event->metadata);
    }

    #[Test]
    public function record_queues_by_default(): void
    {
        $event = Audit::record(type: 'test.queued');

        $this->assertNull($event);

        $this->assertDatabaseHas('audit_events', ['type' => 'test.queued']);
    }

    #[Test]
    public function record_dispatches_the_job_with_the_correct_payload_when_queued(): void
    {
        Queue::fake();

        $actor = User::create(['name' => 'Alice']);

        Audit::record(type: 'test.queued_with_payload', actor: $actor);

        Queue::assertPushed(RecordAuditEvent::class, function (RecordAuditEvent $job) use ($actor) {
            $payload = $job->payload();

            return $payload['type'] === 'test.queued_with_payload'
                && $payload['actor_id'] === $actor->id;
        });
    }
}
