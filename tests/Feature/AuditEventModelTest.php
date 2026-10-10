<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Danielm\LaravelSimpleAudit\Tests\Fixtures\User;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

class AuditEventModelTest extends TestCase
{
    #[Test]
    public function it_cannot_be_updated(): void
    {
        $event = audit()->synchronously()->log('test.event');

        $this->expectException(LogicException::class);

        $event->update(['type' => 'changed']);
    }

    #[Test]
    public function it_cannot_be_deleted(): void
    {
        $event = audit()->synchronously()->log('test.event');

        $this->expectException(LogicException::class);

        $event->delete();
    }

    #[Test]
    public function of_type_scope_filters_by_type(): void
    {
        audit()->synchronously()->log('user.logged_in');
        audit()->synchronously()->log('team.member_added');

        $this->assertSame(1, AuditEvent::ofType('team.member_added')->count());
        $this->assertSame(1, AuditEvent::ofType('user.logged_in')->count());
    }

    #[Test]
    public function of_type_scope_accepts_a_backed_enum(): void
    {
        audit()->synchronously()->log(FakeType::Thing);

        $this->assertSame(1, AuditEvent::ofType(FakeType::Thing)->count());
    }

    #[Test]
    public function for_actor_and_for_subject_scopes_filter_correctly(): void
    {
        $alice = User::create(['name' => 'Alice']);
        $bob = User::create(['name' => 'Bob']);

        audit()->by($alice)->performedOn($bob)->synchronously()->log('team.member_added');
        audit()->by($bob)->performedOn($alice)->synchronously()->log('team.member_added');

        $this->assertSame(1, AuditEvent::forActor($alice)->count());
        $this->assertSame(1, AuditEvent::forSubject($alice)->count());
    }

    #[Test]
    public function between_scope_filters_by_occurred_at_range(): void
    {
        $event = audit()->synchronously()->log('test.event');

        $this->assertSame(
            1,
            AuditEvent::between(now()->subMinute(), now()->addMinute())->count()
        );

        $this->assertSame(
            0,
            AuditEvent::between(now()->addDay(), now()->addDays(2))->count()
        );
    }

    #[Test]
    public function recent_scope_orders_newest_first(): void
    {
        // Set occurred_at explicitly rather than relying on two calls to
        // now() landing in different microseconds.
        $first = AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.first',
            'occurred_at' => now()->subMinute(),
        ]);

        $second = AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.second',
            'occurred_at' => now(),
        ]);

        $ids = AuditEvent::recent()->pluck('id')->all();

        $this->assertSame([$second->id, $first->id], $ids);
    }
}

enum FakeType: string
{
    case Thing = 'fake.thing';
}
