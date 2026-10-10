<?php

namespace Danielm\LaravelSimpleAudit\Tests\Feature;

use Danielm\LaravelSimpleAudit\Jobs\RecordAuditEvent;
use Danielm\LaravelSimpleAudit\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class RecordAuditEventJobTest extends TestCase
{
    #[Test]
    public function handle_persists_the_payload_as_an_audit_event(): void
    {
        $payload = [
            'id' => (string) Str::ulid(),
            'type' => 'test.job_handle',
            'occurred_at' => now(),
            'metadata' => ['foo' => 'bar'],
        ];

        (new RecordAuditEvent($payload))->handle();

        $this->assertDatabaseHas('audit_events', [
            'id' => $payload['id'],
            'type' => 'test.job_handle',
        ]);
    }

    #[Test]
    public function payload_exposes_the_data_the_job_will_persist(): void
    {
        $payload = ['id' => (string) Str::ulid(), 'type' => 'test.inspect'];

        $job = new RecordAuditEvent($payload);

        $this->assertSame($payload, $job->payload());
    }

    #[Test]
    public function failed_logs_the_error_instead_of_throwing(): void
    {
        Log::spy();

        $payload = ['id' => (string) Str::ulid(), 'type' => 'test.will_fail'];
        $job = new RecordAuditEvent($payload);

        $job->failed(new RuntimeException('database unavailable'));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($payload) {
                return $message === 'Failed to persist audit event after retries'
                    && $context['payload'] === $payload
                    && $context['error'] === 'database unavailable';
            });
    }
}
