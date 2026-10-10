<?php

namespace Danielm\LaravelSimpleAudit\Services;

use BackedEnum;
use Danielm\LaravelSimpleAudit\Jobs\RecordAuditEvent;
use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Audit
{
    /**
     * Start a fluent AuditBuilder, e.g.:
     *
     *   Audit::start()->by($actor)->performedOn($targetModel)->log('some.event');
     *
     * Prefer the global audit() helper for brevity.
     */
    public static function start(): AuditBuilder
    {
        return new AuditBuilder;
    }

    /**
     * Record an audit event directly (no builder). By default this is
     * queued so it never adds latency to the request/response cycle.
     */
    public static function record(
        BackedEnum|string $type,
        ?Model $actor = null,
        ?Model $subject = null,
        ?Model $reference = null,
        array $metadata = [],
        bool $synchronous = false,
    ): ?AuditEvent {
        $payload = [
            'id' => (string) Str::ulid(),
            'type' => $type instanceof BackedEnum ? $type->value : $type,

            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),

            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),

            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),

            'metadata' => $metadata,

            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            'request_id' => request()->header('X-Request-Id'),

            'occurred_at' => now(),
        ];

        $modelClass = config('simple-audit.model', AuditEvent::class);

        if ($synchronous) {
            return $modelClass::create($payload);
        }

        RecordAuditEvent::dispatch($payload)
            ->onConnection(config('simple-audit.queue_connection'))
            ->onQueue(config('simple-audit.queue', 'audit'));

        return null;
    }
}
