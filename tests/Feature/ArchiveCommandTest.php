<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class ArchiveCommandTest extends TestCase
{
    #[Test]
    public function it_archives_old_events_and_removes_them_from_the_hot_table(): void
    {
        Storage::fake('local');

        $old = AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.old',
            'occurred_at' => now()->subDays(200),
        ]);

        $recent = AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.recent',
            'occurred_at' => now(),
        ]);

        $this->artisan('simple-audit:archive', ['--days' => 180, '--disk' => 'local'])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('audit_events', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_events', ['id' => $recent->id]);

        $files = Storage::disk('local')->allFiles('simple-audit-archives');
        $this->assertCount(1, $files);
        $this->assertStringContainsString($old->type, Storage::disk('local')->get($files[0]));
    }

    #[Test]
    public function it_does_nothing_when_there_is_nothing_to_archive(): void
    {
        Storage::fake('local');

        AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.recent',
            'occurred_at' => now(),
        ]);

        $this->artisan('simple-audit:archive', ['--days' => 180, '--disk' => 'local'])
            ->expectsOutput('Nothing to archive.')
            ->assertExitCode(0);

        $this->assertSame(0, count(Storage::disk('local')->allFiles('simple-audit-archives')));
    }

    #[Test]
    public function it_falls_back_to_config_defaults_when_options_are_omitted(): void
    {
        Storage::fake('local');

        config(['simple-audit.archive.days' => 1]);
        config(['simple-audit.archive.disk' => 'local']);

        AuditEvent::create([
            'id' => (string) Str::ulid(),
            'type' => 'test.old',
            'occurred_at' => now()->subDays(5),
        ]);

        $this->artisan('simple-audit:archive')->assertExitCode(0);

        $this->assertSame(1, count(Storage::disk('local')->allFiles('simple-audit-archives')));
    }
}
