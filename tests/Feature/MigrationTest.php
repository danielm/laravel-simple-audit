<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class MigrationTest extends TestCase
{
    #[Test]
    public function it_creates_the_audit_events_table_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('audit_events'));

        $this->assertTrue(Schema::hasColumns('audit_events', [
            'id',
            'type',
            'actor_type', 'actor_id',
            'subject_type', 'subject_id',
            'reference_type', 'reference_id',
            'metadata',
            'ip',
            'user_agent',
            'request_id',
            'occurred_at',
        ]));
    }

    #[Test]
    public function the_table_name_respects_the_config_value(): void
    {
        config(['simple-audit.table' => 'audit_events']);

        $this->assertTrue(Schema::hasTable(config('simple-audit.table')));
    }
}
