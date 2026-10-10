<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Jobs\RecordAuditEvent;
use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Danielm\LaravelSimpleAudit\Services\AuditBuilder;
use Danielm\LaravelSimpleAudit\Tests\Fixtures\Project;
use Danielm\LaravelSimpleAudit\Tests\Fixtures\User;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;

class AuditBuilderTest extends TestCase
{
    #[Test]
    public function the_global_helper_returns_a_builder(): void
    {
        $this->assertInstanceOf(AuditBuilder::class, audit());
    }

    #[Test]
    public function it_records_actor_subject_reference_and_properties(): void
    {
        $actor = User::create(['name' => 'Alice']);
        $subject = User::create(['name' => 'Bob']);
        $reference = Project::create(['name' => 'Apollo']);

        $event = audit()
            ->by($actor)
            ->performedOn($subject)
            ->on($reference)
            ->withProperties(['role' => 'member'])
            ->synchronously()
            ->log('team.member_added');

        $this->assertInstanceOf(AuditEvent::class, $event);

        $this->assertDatabaseHas('audit_events', [
            'id' => $event->id,
            'type' => 'team.member_added',
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => $actor->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->id,
        ]);

        $this->assertSame(['role' => 'member'], $event->fresh()->metadata);
    }

    #[Test]
    public function it_accepts_a_backed_enum_as_the_type(): void
    {
        $event = audit()->synchronously()->log(FakeEventType::Something);

        $this->assertSame('fake.something', $event->type);
    }

    #[Test]
    public function with_property_merges_a_single_key(): void
    {
        $event = audit()
            ->withProperties(['a' => 1])
            ->withProperty('b', 2)
            ->synchronously()
            ->log('test.event');

        $this->assertSame(['a' => 1, 'b' => 2], $event->metadata);
    }

    #[Test]
    public function the_description_argument_is_stored_under_metadata(): void
    {
        $event = audit()
            ->synchronously()
            ->log('settings.changed', 'Rotated after suspected leak');

        $this->assertSame('Rotated after suspected leak', $event->metadata['description']);
    }

    #[Test]
    public function actor_subject_and_reference_are_all_optional(): void
    {
        $event = audit()->synchronously()->log('system.cleanup_ran');

        $this->assertNull($event->actor_type);
        $this->assertNull($event->subject_type);
        $this->assertNull($event->reference_type);
    }

    // ----- Synchronous vs. queued write path ---------------------------

    #[Test]
    public function synchronously_writes_immediately_and_returns_the_model(): void
    {
        $event = audit()->synchronously()->log('test.sync_event');

        $this->assertInstanceOf(AuditEvent::class, $event);
        $this->assertDatabaseHas('audit_events', ['id' => $event->id, 'type' => 'test.sync_event']);
    }

    #[Test]
    public function by_default_it_dispatches_a_queued_job_instead_of_writing_immediately(): void
    {
        Queue::fake();

        $result = audit()->log('test.queued_event');

        $this->assertNull($result, '->log() returns null when the write is queued, since nothing is persisted yet.');
        $this->assertDatabaseMissing('audit_events', ['type' => 'test.queued_event']);

        Queue::assertPushed(RecordAuditEvent::class, function (RecordAuditEvent $job) {
            return $job->payload()['type'] === 'test.queued_event';
        });
    }

    #[Test]
    public function the_queued_job_is_pushed_onto_the_configured_queue_and_connection(): void
    {
        config(['simple-audit.queue' => 'custom-audit-queue']);
        config(['simple-audit.queue_connection' => 'custom-connection']);

        Queue::fake();

        audit()->log('test.queued_event');

        Queue::assertPushedOn('custom-audit-queue', RecordAuditEvent::class);

        Queue::assertPushed(RecordAuditEvent::class, function (RecordAuditEvent $job) {
            return $job->connection === 'custom-connection';
        });
    }

    #[Test]
    public function the_queued_job_payload_carries_actor_subject_reference_and_properties(): void
    {
        $actor = User::create(['name' => 'Alice']);
        $subject = User::create(['name' => 'Bob']);

        Queue::fake();

        audit()
            ->by($actor)
            ->performedOn($subject)
            ->withProperties(['role' => 'member'])
            ->log('team.member_added');

        Queue::assertPushed(RecordAuditEvent::class, function (RecordAuditEvent $job) use ($actor, $subject) {
            $payload = $job->payload();

            return $payload['type'] === 'team.member_added'
                && $payload['actor_id'] === $actor->id
                && $payload['subject_id'] === $subject->id
                && $payload['metadata'] === ['role' => 'member'];
        });
    }

    #[Test]
    public function without_synchronously_the_sync_queue_driver_still_persists_the_row_end_to_end(): void
    {
        // queue.default is "sync" in the test environment (see TestCase),
        // so dispatching the job runs it inline — this exercises the full
        // queued code path end-to-end, as opposed to Queue::fake() above
        // which only asserts the dispatch itself.
        $result = audit()->log('test.end_to_end_queued');

        $this->assertNull($result);
        $this->assertDatabaseHas('audit_events', ['type' => 'test.end_to_end_queued']);
    }

    // ----- Swappable model class (simple-audit.model config) ------------

    #[Test]
    public function it_uses_the_configured_model_class_when_writing_synchronously(): void
    {
        config(['simple-audit.model' => CustomAuditEvent::class]);

        $event = audit()->synchronously()->log('test.custom_model');

        $this->assertInstanceOf(CustomAuditEvent::class, $event);
    }

    #[Test]
    public function it_uses_the_configured_model_class_when_writing_via_the_queue(): void
    {
        config(['simple-audit.model' => CustomAuditEvent::class]);

        audit()->log('test.custom_model_queued'); // runs inline via the sync queue driver

        $stored = CustomAuditEvent::where('type', 'test.custom_model_queued')->first();

        $this->assertInstanceOf(CustomAuditEvent::class, $stored);
    }
}

enum FakeEventType: string
{
    case Something = 'fake.something';
}

class CustomAuditEvent extends AuditEvent
{
    //
}
