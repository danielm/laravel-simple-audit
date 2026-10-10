<?php

namespace Danielm\LaravelSimpleAudit\Jobs;

use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecordAuditEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(private readonly array $payload) {}

    /**
     * The audit row data this job will persist. Mainly useful for
     * inspecting a faked/pushed job in tests.
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function handle(): void
    {
        $modelClass = config('simple-audit.model', AuditEvent::class);

        $modelClass::create($this->payload);
    }

    /**
     * If every retry fails, don't lose the event silently — log it so
     * it can be reconciled manually. Consider alerting here too.
     */
    public function failed(\Throwable $e): void
    {
        logger()->error('Failed to persist audit event after retries', [
            'payload' => $this->payload,
            'error' => $e->getMessage(),
        ]);
    }
}
