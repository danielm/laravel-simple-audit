<?php

namespace Danielm\LaravelSimpleAudit\Console\Commands;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Exports audit events older than N days to cold storage as
 * newline-delimited JSON, then removes them from the hot table.
 *
 * Schedule in app/Console/Kernel.php (or routes/console.php on Laravel 11+):
 *
 *   $schedule->command('simple-audit:archive')->monthly();
 */
class ArchiveAuditEvents extends Command
{
    protected $signature = 'simple-audit:archive
        {--days= : Archive events older than this many days (defaults to config simple-audit.archive.days)}
        {--disk= : Filesystem disk to archive into (defaults to config simple-audit.archive.disk)}
        {--chunk=1000 : Rows per batch}';

    protected $description = 'Archive old audit events to cold storage and remove them from the hot table';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('simple-audit.archive.days', 180));
        $disk = (string) ($this->option('disk') ?? config('simple-audit.archive.disk', 's3'));
        $chunkSize = (int) $this->option('chunk');

        $cutoff = now()->subDays($days);
        $table = config('simple-audit.table', 'audit_events');
        $connection = config('simple-audit.connection');

        $path = sprintf('simple-audit-archives/%s_%s.ndjson', $cutoff->format('Y-m'), Str::random(8));

        $count = 0;
        $lines = [];

        AuditEvent::query()
            ->where('occurred_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($events) use (&$lines, &$count) {
                foreach ($events as $event) {
                    $lines[] = json_encode($event->toArray());
                    $count++;
                }
            });

        if ($count === 0) {
            $this->info('Nothing to archive.');

            return self::SUCCESS;
        }

        Storage::disk($disk)->put($path, implode("\n", $lines));

        // The model blocks deletes (immutability guard) — use the query
        // builder directly here, specifically for this retention job.
        DB::connection($connection)
            ->table($table)
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $this->info("Archived {$count} events to [{$disk}:{$path}] and removed them from {$table}.");

        return self::SUCCESS;
    }
}
