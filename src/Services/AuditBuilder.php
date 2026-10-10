<?php

namespace Danielm\LaravelSimpleAudit\Services;

use BackedEnum;
use Danielm\LaravelSimpleAudit\Jobs\RecordAuditEvent;
use Danielm\LaravelSimpleAudit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Fluent builder for recording audit events, built on actor / subject /
 * reference columns.
 *
 *   audit()
 *       ->by($actor)
 *       ->performedOn($targetModel)
 *       ->withProperties(['ip' => $request->ip()])
 *       ->log('user.logged_in'); // any string, or a BackedEnum you define
 */
class AuditBuilder
{
    protected ?Model $actor = null;

    protected ?Model $subject = null;

    protected ?Model $reference = null;

    protected array $properties = [];

    protected bool $synchronous = false;

    /**
     * Who performed the action. Omit for system/console-initiated events.
     */
    public function by(?Model $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * What the action happened to.
     */
    public function performedOn(?Model $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * A related record that gives the event context without being the
     * subject itself — e.g. a parent record, an originating request, or
     * some other entity worth linking without it being the main subject.
     */
    public function on(?Model $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Merge in free-form, non-sensitive context. Never pass secrets/tokens.
     */
    public function withProperties(array $properties): static
    {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }

    public function withProperty(string $key, mixed $value): static
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * Write synchronously, inside the current request/transaction, instead
     * of queueing. Use for events that must not be lost (e.g. security-
     * sensitive actions) even if the queue is temporarily down.
     */
    public function synchronously(bool $synchronous = true): static
    {
        $this->synchronous = $synchronous;

        return $this;
    }

    /**
     * Finalize and persist the event. $type is your own event-type
     * identifier — a plain string or a BackedEnum you define in your app
     * (this package has no opinion on what event types exist).
     *
     * $description is optional free text stored in properties['description'],
     * mirroring activitylog's ->log('some text').
     */
    public function log(BackedEnum|string $type, ?string $description = null): ?AuditEvent
    {
        $properties = $this->properties;

        if ($description !== null) {
            $properties['description'] = $description;
        }

        $payload = [
            'id' => (string) Str::ulid(),
            'type' => $type instanceof BackedEnum ? $type->value : $type,

            'actor_type' => $this->actor?->getMorphClass(),
            'actor_id' => $this->actor?->getKey(),

            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),

            'reference_type' => $this->reference?->getMorphClass(),
            'reference_id' => $this->reference?->getKey(),

            'metadata' => $properties,

            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            'request_id' => request()->header('X-Request-Id'),

            'occurred_at' => now(),
        ];

        $modelClass = config('simple-audit.model', AuditEvent::class);

        if ($this->synchronous) {
            return $modelClass::create($payload);
        }

        RecordAuditEvent::dispatch($payload)
            ->onConnection(config('simple-audit.queue_connection'))
            ->onQueue(config('simple-audit.queue', 'audit'));

        return null;
    }
}
