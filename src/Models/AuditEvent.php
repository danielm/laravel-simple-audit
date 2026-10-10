<?php

namespace Danielm\LaravelSimpleAudit\Models;

use BackedEnum;
use Danielm\LaravelSimpleAudit\Observers\AuditEventObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AuditEventObserver::class)]
class AuditEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('simple-audit.table', 'audit_events'));

        if ($connection = config('simple-audit.connection')) {
            $this->setConnection($connection);
        }
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // ----- Query scopes -----------------------------------------------

    #[Scope]
    protected function ofType(Builder $query, BackedEnum|string $type): Builder
    {
        return $query->where('type', $type instanceof BackedEnum ? $type->value : $type);
    }

    #[Scope]
    protected function forActor(Builder $query, Model $actor): Builder
    {
        return $query->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey());
    }

    #[Scope]
    protected function forSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    #[Scope]
    protected function between(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    #[Scope]
    protected function recent(Builder $query): Builder
    {
        return $query->orderByDesc('occurred_at');
    }
}
